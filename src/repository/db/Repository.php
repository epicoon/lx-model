<?php

namespace lx\model\repository\db;

use lx;
use lx\ArrayHelper;
use lx\DbConnection;
use lx\DbConnector;
use lx\model\Model;
use lx\model\repository\db\tools\crud\CrudProcessor;
use lx\model\repository\db\tools\crud\ModelFieldsConverter;
use lx\model\repository\db\tools\crud\RelatedLoader;
use lx\model\repository\db\tools\holdStack\HoldStack;
use lx\model\repository\db\tools\UnitMap;
use lx\model\repository\RepositoryInterface;
use lx\model\repository\db\migrationBuilder\MigrationBuilder;
use lx\model\repository\db\migrationExecutor\MigrationExecutor;
use lx\model\repository\db\tools\MigrationConductor;
use lx\model\repository\MigrationInterface;
use lx\model\repository\ReportInterface;
use lx\model\repository\db\comparator\ModelsComparator;
use lx\model\repository\db\tools\RepositoryContext;
use lx\model\managerTools\ModelsContext;
use lx\model\repository\db\migrationExecutor\MigrationExecuteReport;
use lx\model\repository\db\comparator\CompareRepositoryReport;

class Repository implements RepositoryInterface
{
    const CONNECTION_KEY_MAIN = 'main';
    const CONNECTION_KEY_REPLICA = 'replica';

    private RepositoryContext $context;
    private array $config;
    private HoldStack $holdStack;
    private UnitMap $unitMap;
    private CrudProcessor $crudProcessor;
    private ?DbConnection $readDb = null;
    private ?DbConnection $writeDb = null;

    public function hasConnection(): bool
    {
        return $this->getConnector() !== null;
    }

    public function setContext(ModelsContext $context): void
    {
        $this->context = new RepositoryContext($context, $this);
        $this->holdStack = new HoldStack();
        $this->unitMap = new UnitMap();
        $this->crudProcessor = new CrudProcessor($this);
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function isSingle(): bool
    {
        return false;
    }

    public function getContext(): RepositoryContext
    {
        return $this->context;
    }

    /**
     * @return mixed
     */
    public function getConfig(string $key)
    {
        return $this->config[$key] ?? null;
    }

    public function checkModelsStatus(?array $modelNames = null): ReportInterface
    {
        if (!$this->hasConnection()) {
            $report = new CompareRepositoryReport();
            $report->addToErrors('Connection to repository does not exist');
            return $report;
        }

        $comparator = new ModelsComparator($this->context);
        return $comparator->run($modelNames);
    }

    public function createNewMigration(): void
    {
        $builder = new MigrationBuilder($this->context);
        $builder->createEmpty();
    }

    public function buildMigrations(ReportInterface $changes): ReportInterface
    {
        $builder = new MigrationBuilder($this->context);
        return $builder->createByReport($changes);
    }

    public function executeMigrations(?int $count = null): ReportInterface
    {
        if (!$this->hasConnection()) {
            $report = new MigrationExecuteReport();
            $report->addToMigrationErrors([
                'migration' => 'ALL',
                'error' => 'Connection to repository does not exist',
            ]);
            return $report;
        }

        $executor = new MigrationExecutor($this->context);
        return $executor->run($count);
    }

    public function rollbackMigrations(?int $count = null): ReportInterface
    {
        if (!$this->hasConnection()) {
            $report = new MigrationExecuteReport();
            $report->addToMigrationErrors([
                'migration' => 'ALL',
                'error' => 'Connection to repository does not exist',
            ]);
            return $report;
        }
        
        $executor = new MigrationExecutor($this->context);
        return $executor->run($count, true);
    }

    public function hasUnappliedMigrations(): bool
    {
        if (!$this->hasConnection()) {
            return false;
        }

        $conductor = new MigrationConductor($this->context);
        return $conductor->hasUnapplied();
    }

    /**
     * @return array<MigrationInterface>
     */
    public function getMigrations(): array
    {
        if (!$this->hasConnection()) {
            return [];
        }

        $conductor = new MigrationConductor($this->context);
        return $conductor->getMigrations();
    }

    public function getMigration(string $name): ?MigrationInterface
    {
        if (!$this->hasConnection()) {
            return null;
        }
        
        $conductor = new MigrationConductor($this->context);
        return $conductor->getMigration($name);
    }
    
    public function isOnHold(): bool
    {
        return $this->holdStack->isActive();
    }

    public function hold(): void
    {
        $this->holdStack->mount();
    }

    public function drop(): void
    {
        $this->holdStack->pop();
    }

    public function commit(bool $force = false): bool
    {
        if (!$this->hasConnection()) {
            return false;
        }

        if (!$this->isOnHold()) {
            return false;
        }

        $list = [];
        if ($force) {
            $list = $this->holdStack->pop();
        } else {
            $this->holdStack->flatten();
            if ($this->holdStack->isFlat()) {
                $list = $this->holdStack->getList();
                $this->holdStack->reset();
            }
        }

        if (empty($list)) {
            return true;
        }

        $forSave = [];
        $forDelete = [];
        /** @var Model $model */
        foreach ($list as $model) {
            if ($model->getMetaData()->getProperty('forSave')) {
                $forSave[] = $model;
            } else {
                $forDelete[] = $model;
            }
            $model->getMetaData()->dropProperty('forSave');
        }
        $forDeleteWithIds = [];
        foreach ($forDelete as $model) {
            if ($model->getId()) {
                $forDeleteIds[] = [
                    'model' => $model,
                    'id' => $model->getId(),
                ];
            }
        }

        $this->getMainDb()->transactionBegin();
        if (!$this->crudProcessor->saveModels($forSave)) {
            $this->getMainDb()->transactionRollback();
            return false;
        }

        if (!$this->crudProcessor->deleteModels($forDelete)) {
            $this->getMainDb()->transactionRollback();
            return false;
        }

        $this->getMainDb()->transactionCommit();
        $this->unitMap->registerList($forSave);
        $this->unitMap->unregisterList($forDeleteWithIds);
        return true;
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * CRUD
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    public function getCount(string $modelName, ?array $condition = null): int
    {
        if (!$this->hasConnection()) {
            return 0;
        }

        $nameConverter = $this->context->getNameConverter();
        $tableName = $nameConverter->getTableName($modelName);
        $db = $this->getReplicaDb();
        $table = $db->getTable($tableName);
        if (!$table) {
            return 0;
        }

        if ($condition) {
            $condition = ModelFieldsConverter::toRepositoryForCondition($this->context, $modelName, $condition);
        }

        $result = $table->select('count(*)', $condition);
        if ($result === false) {
            //TODO exception?
            return 0;
        }

        return (int)$result[0]['count'];
    }

    public function saveModel(Model $model): bool
    {
        if (!$this->hasConnection()) {
            return false;
        }

        if (!$this->holdStack->isActive()) {
            if (!$this->crudProcessor->saveModel($model)) {
                return false;
            }

            $this->unitMap->register($model);
            return true;
        }

        if ($model->getMetaData()->getProperty('forSave') !== null) {
            if ($model->getMetaData()->getProperty('forSave') === false) {
                //TODO exception?
                return false;
            }

            return true;
        }

        $model->getMetaData()->setProperty('forSave', true);
        $this->holdStack->add($model);
        return true;
    }

    public function deleteModel(Model $model): bool
    {
        if (!$this->hasConnection()) {
            return false;
        }

        if (!$this->holdStack->isActive()) {
            $id = $model->getId();
            if (!$this->crudProcessor->deleteModel($model)) {
                return false;
            }

            if ($id) {
                $this->unitMap->unregister($model, $id);
            }

            return true;
        }

        if ($model->getMetaData()->getProperty('forSave') !== null) {
            if ($model->getMetaData()->getProperty('forSave') === true) {
                //TODO exception?
                return false;
            }

            return true;
        }

        $model->getMetaData()->setProperty('forSave', false);
        $this->holdStack->add($model);
        return true;
    }

    /**
     * @param int|array $condition
     */
    public function findModel(string $modelName, $condition, bool $useUnitMap = true): ?Model
    {
        if (!$this->hasConnection()) {
            return null;
        }

        if (is_integer($condition)) {
            $condition = ['id' => $condition];
        }

        if (isset($condition['id'])) {
            $id = $condition['id'];
            $model = $useUnitMap ? $this->unitMap->get($modelName, $id) : null;
            if (!$model) {
                $model = $this->crudProcessor->findModel($modelName, $id);
                if ($useUnitMap) {
                    $this->unitMap->register($model);
                }
            }
            return $model;
        }

        //TODO LIMIT 1
        $models = $this->findModels($modelName, $condition, $useUnitMap);
        return $models[0] ?? null;
    }

    /**
     * @return null|iterable<Model>
     */
    public function findModelAsArray(string $modelName, int $id, bool $useUnitMap = true): ?iterable
    {
        if (!$this->hasConnection()) {
            return null;
        }

        $model = $useUnitMap ? $this->unitMap->get($modelName, $id) : null;
        if ($model) {
            return $model->getFields();
        }

        return $this->crudProcessor->findModelAsArray($modelName, $id);
    }

    /**
     * @return iterable<Model>
     */
    public function findModels(string $modelName, ?array $condition = null, bool $useUnitMap = true): iterable
    {
        if (!$this->hasConnection()) {
            return [];
        }

        if ($condition) {
            $condition = ModelFieldsConverter::toRepositoryForCondition($this->context, $modelName, $condition);
        }

        if (!$useUnitMap) {
            return $this->crudProcessor->findModels($modelName, $condition);
        }

        $nameConverter = $this->context->getNameConverter();
        $tableName = $nameConverter->getTableName($modelName);
        $table = $this->getReplicaDb()->getTable($tableName);
        $data = $table->select('id', $condition);
        $ids = ArrayHelper::getColumn($data, 'id');

        //TODO make it more nice
        if (array_key_exists('ORDER BY', $condition)) {
            $models = $this->findModelsByIds($modelName, $ids);
            $map = array_flip($ids);
            $result = array_fill(0, count($ids), 0);
            /** @var lx\ModelInterface $model */
            foreach ($models as $model) {
                $result[$map[$model->getId()]] = $model;
            }
            return $result;
        } else {
            return $this->findModelsByIds($modelName, $ids);
        }
    }

    /**
     * @return iterable<Model>
     */
    public function findModelsByIds(string $modelName, array $ids): iterable
    {
        if (!$this->hasConnection()) {
            return [];
        }

        $models = $this->unitMap->getList($modelName, $ids);

        $registeredIds = [];
        foreach ($models as $model) {
            $registeredIds[] = $model->getId();
        }
        $unregisteredIds = array_diff($ids, $registeredIds);
        if (!empty($unregisteredIds)) {
            $unregisteredModels = $this->crudProcessor->findModels($modelName, ['id' => $unregisteredIds]);
            $this->unitMap->registerList($unregisteredModels);
            $models = array_merge($models, $unregisteredModels);
        }

        return $models;
    }

    /**
     * @return iterable<Model>
     */
    public function findRelatedModels(Model $model, string $relationName): iterable
    {
        if (!$this->hasConnection()) {
            return [];
        }

        $loader = new RelatedLoader($this);
        return $loader->loadForModel($model, $relationName);
    }

    /**
     * @param iterable<Model> $models
     */
    public function saveModels(iterable $models): bool
    {
        if (!$this->hasConnection()) {
            return false;
        }

        if ($this->holdStack->isActive()) {
            foreach ($models as $model) {
                $this->saveModel($model);
            }

            return true;
        }

        $this->getMainDb()->transactionBegin();
        if (!$this->crudProcessor->saveModels($models)) {
            $this->getMainDb()->transactionRollback();
            return false;
        }

        $this->getMainDb()->transactionCommit();
        $this->unitMap->registerList($models);
        return true;
    }

    /**
     * @param iterable<Model> $models
     */
    public function deleteModels(iterable $models): bool
    {
        if (!$this->hasConnection()) {
            return false;
        }

        if ($this->holdStack->isActive()) {
            foreach ($models as $model) {
                $this->deleteModel($model);
            }

            return true;
        }

        $this->getMainDb()->transactionBegin();
        $forUnregister = [];
        foreach ($models as $model) {
            if ($model->getId()) {
                $forUnregister[] = [
                    'model' => $model,
                    'id' => $model->getId(),
                ];
            }
        }
        if (!$this->crudProcessor->deleteModels($models)) {
            $this->getMainDb()->transactionRollback();
            return false;
        }

        $this->getMainDb()->transactionCommit();
        $this->unitMap->unregisterList($forUnregister);
        return true;
    }

    public function deleteModelsByCondition(string $modelName, ?array $condition = null): void
    {
        if (!$this->hasConnection()) {
            return;
        }
        
        $nameConverter = $this->context->getNameConverter();
        $tableName = $nameConverter->getTableName($modelName);
        $table = $this->getMainDb()->getTable($tableName);

        if ($condition) {
            $condition = ModelFieldsConverter::toRepositoryForCondition($this->context, $modelName, $condition);
        }

        $ids = $table->select('id', $condition);
        $ids = ArrayHelper::getColumn($ids, 'id');

        $table->delete($condition);
        $this->unitMap->unregisterByModelName($modelName, $ids);
    }

    //TODO queryBuilder()->delete('from {{ModelName}} where {{ModelName.fieldName}} = :val')->addParam(val, $val)->execute()...





    public function getMainDb(): ?DbConnection
    {
        if (!$this->hasConnection()) {
            return null;
        }
        
        if (!$this->writeDb) {
            $connector = $this->getConnector();
            $key = $this->getConfig(self::CONNECTION_KEY_MAIN);
            $this->writeDb = $key
                ? $connector->getConnection($key) ?? null
                : $connector->getMainConnection() ?? null;
        }

        return $this->writeDb;
    }

    public function getReplicaDb(): ?DbConnection
    {
        if (!$this->hasConnection()) {
            return null;
        }

        if (!$this->readDb) {
            $connector = $this->getConnector();
            $key = $this->getConfig(self::CONNECTION_KEY_REPLICA);
            $this->readDb = $key
                ? $connector->getConnection($key) ?? null
                : $connector->getReplicaConnection() ?? null;
        }

        return $this->readDb;
    }

    protected function getConnector(): ?DbConnector
    {
        return $this->context->getService()->dbConnector ?? lx::$app->dbConnector;
    }
}

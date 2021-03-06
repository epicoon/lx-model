<?php

namespace lx\model\repository\db\migrationExecutor\actions\content;

use Exception;
use lx\model\Model;
use lx\model\repository\db\migrationExecutor\actions\BaseMigrationAction;
use lx\model\repository\db\tools\SyncSchema;
use lx\model\repository\db\tools\SysTablesProvider;

/**
 * Class AddModelAction
 * @package lx\model\repository\db\migrationExecutor\actions\content
 */
class AddModelsAction extends BaseMigrationAction
{
    private bool $inversed = false;

    public function inverse(): BaseMigrationAction
    {
        if (!$this->isSupportRollback()) {
            $name = $this->migration->getName();
            throw new Exception("Migrations $name does not support rollback");
        }

        $this->inversed = true;
        return $this;
    }

    protected function execute(): void
    {
        if ($this->inversed) {
            $this->rollback();
        } else {
            $this->apply();
        }
    }

    private function apply(): void
    {
        $repo = $this->context->getRepository();
        $repo->hold();

        $newModels = [];
        $vars = [];
        $withRelations = [];
        $deferredModels = [];
        $list = $this->data['list'] ?? [];
        foreach ($list as $modelData) {
            $modelName = null;
            $modelVarName = null;
            foreach ($modelData as $key => $value) {
                if ($key[0] == '$') {
                    $modelName = $value;
                    if ($key != '$') {
                        $modelVarName = $key;
                    }
                    unset($modelData[$key]);
                    break;
                }
            }

            if ($modelName === null) {
                continue;
            }

            $syncSchema = new SyncSchema($this->context, $modelName);
            $modelSchema = $syncSchema->restoreModelSchema()->getModelSchema();
            $fields = [];
            $deferredFields = [];
            $relations = [];
            foreach ($modelData as $key => $value) {
                if ($modelSchema->hasField($key)) {
                    if (strpos($value, '<?=') === 0) {
                        $deferredFields[$key] = $value;
                        continue;
                    }

                    $fields[$key] = $value;
                } elseif ($modelSchema->hasRelation($key)) {
                    $relations[$key] = $value;
                }
            }

            $model = SyncSchema::createAnonymousModel($this->context, [
                'modelName' => $modelName,
                'modelFields' => $fields,
            ]);
            $newModels[] = $model;

            if ($modelVarName !== null) {
                $vars[$modelVarName] = $model;
            }

            if (!empty($deferredFields)) {
                $deferredModels[] = [
                    'model' => $model,
                    'fields' => $deferredFields,
                ];
            }

            if (!empty($relations)) {
                $withRelations[] = [
                    'model' => $model,
                    'relations' => $relations,
                ];
            }

            if (empty($deferredFields)) {
                $model->save();
            }
        }

        foreach ($deferredModels as $data) {
            /** @var Model $model */
            $model = $data['model'];
            $fields = $data['fields'];
            foreach ($fields as $key => $value) {
                $value = preg_replace('/(^<\?=|\?>$)/', '', $value);
                $value = $this->eval($vars, $value);
                $model->setField($key, $value);
            }

            $model->save();
        }

        foreach ($withRelations as $data) {
            /** @var Model $model */
            $model = $data['model'];
            $relationsData = $data['relations'];
            foreach ($relationsData as $relationName => $relationData) {
                $relation = $model->getSchema()->getRelation($relationName);
                foreach ($relationData as $relId) {
                    if (is_string($relId)) {
                        if (!array_key_exists($relId, $vars)) {
                            continue;
                        }
                        $model->setRelated($relationName, $vars[$relId]);
                        continue;
                    }

                    $relFields = $repo->findModelAsArray($relation->getRelatedModelName(), $relId);
                    $relModel = SyncSchema::createAnonymousModel($this->context, [
                        'modelName' => $relation->getRelatedModelName(),
                        'modelFields' => $relFields,
                    ]);
                    $relModel->setId($relId);
                    $model->setRelated($relationName, $relModel);
                }
            }
        }

        $repo->commit();

        if (!$this->isSupportRollback()) {
            return;
        }

        $sysTablesProvider = new SysTablesProvider($this->context);
        $table = $sysTablesProvider->getTable(SysTablesProvider::MIGRATIONS_META_DATA);
        $metaData = [];
        /** @var Model $model */
        foreach ($newModels as $model) {
            $metaData[] = [
                $this->migration->getVersion(),
                $model->getModelName(),
                $model->getId(),
            ];
        }
        $table->insert(['version', 'model_name', 'model_id'], $metaData, false);
    }

    private function rollback(): void
    {
        $sysTablesProvider = new SysTablesProvider($this->context);
        $table = $sysTablesProvider->getTable(SysTablesProvider::MIGRATIONS_META_DATA);
        $metaData = $table->select(['model_name', 'model_id'], [
            'version' => $this->migration->getVersion(),
        ]);

        $repo = $this->context->getRepository();
        $repo->hold();
        foreach ($metaData as $row) {
            $modelName = $row['model_name'];
            $modelId = $row['model_id'];

            $fields = $repo->findModelAsArray($modelName, $modelId);
            $model = SyncSchema::createAnonymousModel($this->context, [
                'modelName' => $modelName,
                'modelFields' => $fields,
            ]);
            $model->setId($modelId);

            $this->dropRelations($model);
            $model->delete();
        }
        $repo->commit();

        $table->delete([
            'version' => $this->migration->getVersion(),
        ]);
    }

    private function dropRelations(Model $model): void
    {
        $db = $this->context->getRepository()->getMainDb();
        $nameConverter = $this->context->getNameConverter();

        $schema = $model->getSchema();
        foreach ($schema->getRelations() as $relation) {
            if ($relation->isManyToMany()) {
                $relTableName = $nameConverter->getManyToManyTableName(
                    $schema->getModelName(),
                    $relation->getName(),
                    $relation->getRelatedModelName(),
                    $relation->getRelatedAttributeName()
                );
                $relTable = $db->table($relTableName);
                $fkName = $nameConverter->getRelationName($schema->getModelName());
                $relTable->delete([
                    $fkName => $model->getId(),
                ]);
                continue;
            }

            if ($relation->isFkHolder()) {
                continue;
            }

            $relTableName = $nameConverter->getTableName($relation->getRelatedModelName());
            $relTable = $db->table($relTableName);
            $fkName = $nameConverter->getRelationName($schema->getModelName(), $relation->getName());
            $relTable->update([$fkName => null], [
                $fkName => $model->getId(),
            ]);
        }
    }

    /**
     * @param array $_vars
     * @param string $_code
     * @return mixed
     */
    private function eval(array $_vars, string $_code)
    {
        $temp = [];
        foreach ($_vars as $key => $value) {
            $key = preg_replace('/^\$/', '', $key);
            $temp[$key] = $value;
        }

        $_vars = $temp;
        unset($temp);
        unset($key);
        unset($value);

        extract($_vars);
        return eval('return ' . $_code . ';');
    }

    private function isSupportRollback(): bool
    {
        if (array_key_exists('rollback', $this->data)) {
            return (bool)$this->data['rollback'];
        }

        return true;
    }
}

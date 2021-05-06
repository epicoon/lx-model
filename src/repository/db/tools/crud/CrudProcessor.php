<?php

namespace lx\model\repository\db\tools\crud;

use lx\DB;
use lx\DbTable;
use lx\model\Model;
use lx\model\repository\db\Repository;
use lx\model\repository\db\tools\RepositoryContext;
use lx\model\schema\relation\ModelRelation;

class CrudProcessor
{
    const RELATION_FOR_ADD = 'add';
    const RELATION_FOR_DELETE = 'del';

    private Repository $repository;
    private RepositoryContext $context;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        $this->context = $repository->getContext();
    }

    public function findModelAsArray(string $modelName, int $id): ?array
    {
        $table = $this->getTableByModelName($this->repository->getReplicaDb(), $modelName);
        if (!$table) {
            return null;
        }

        $data = $table->select('*', ['id' => $id]);
        if (empty($data)) {
            return null;
        }

        return $data[0];
    }

    public function findModel(string $modelName, int $id): ?Model
    {
        $fields = $this->findModelAsArray($modelName, $id); //$data[0];
        if (!$fields) {
            return null;
        }
        $id = $fields['id'];
        unset($fields['id']);

        $class = $this->context->getModelManager()->getModelClassName($modelName);
        $allFields = ModelFieldsConverter::fromRepository($this->context, $modelName, $fields);
        /** @var Model $model */
        $model = new $class($allFields);
        $model->setId($id);
        return $model;
    }

    public function saveModel(Model $model): bool
    {
        if (!$model->isChanged()) {
            return true;
        }

        $massSaveProcessor = new MassSaveProcessor($this->repository);
        $list = $massSaveProcessor->analyzeModelsList([$model]);

        $forSave = $list['forSave'];
        if (!empty($forSave)) {
            if (count($forSave) > 1) {
                $massSaveProcessor->setModels($forSave);
                if (!$massSaveProcessor->run()) {
                    return false;
                }
            } else {
                /** @var Model $modelForSave */
                $modelForSave = $forSave[0];
                $table = $this->getTableByModel($this->repository->getMainDb(), $modelForSave);
                if (!$table) {
                    return false;
                }

                $columns = ModelFieldsConverter::toRepositoryForModel($this->context, $modelForSave);
                if ($modelForSave->isNew()) {
                    $id = $table->insert($columns);
                    $modelForSave->setId($id);
                } else {
                    $table->update($columns, ['id' => $modelForSave->getId()]);
                }

                $modelForSave->commitChanges();
            }
        }

        list($add, $delete) = $this->splitManyToManyRelations($list['forRelationsUpdate']);
        $this->addManyToManyRelations($add);
        $this->deleteManyToManyRelations($delete);

        return true;
    }

    public function deleteModel(Model $model): bool
    {
        if ($model->isNew()) {
            return true;
        }

        $table = $this->getTableByModel($this->repository->getMainDb(), $model);
        if (!$table) {
            return false;
        }

        $table->delete(['id' => $model->getId()]);
        $model->dropId();
        return true;
    }

    public function findModels(string $modelName, ?array $condition = null): array
    {
        $table = $this->getTableByModelName($this->repository->getReplicaDb(), $modelName);
        if (!$table) {
            return [];
        }

        if ($condition) {
            $condition = ModelFieldsConverter::toRepositoryForCondition($this->context, $modelName, $condition);
        }

        $data = $table->select('*', $condition);
        if (empty($data)) {
            return [];
        }

        $class = $this->context->getModelManager()->getModelClassName($modelName);
        $result = [];
        foreach ($data as $fields) {
            $id = $fields['id'];
            unset($fields['id']);
            $allFields = ModelFieldsConverter::fromRepository($this->context, $modelName, $fields);
            /** @var Model $model */
            $model = new $class($allFields);
            $model->setId($id);
            $result[] = $model;
        }

        return $result;
    }

    /**
     * @param iterable<Model> $models
     */
    public function saveModels(iterable $models): bool
    {
        $processor = new MassSaveProcessor($this->repository);
        $list = $processor->analyzeModelsList($models);

        /** @var Model[] $modelsForSave */
        $modelsForSave = $list['forSave'];
        $processor->setModels($modelsForSave);

        $result = $processor->run();
        if (!$result) {
            return false;
        }

        list($add, $delete) = $this->splitManyToManyRelations($list['forRelationsUpdate']);
        $this->addManyToManyRelations($add);
        $this->deleteManyToManyRelations($delete);

        return true;
    }

    /**
     * @param iterable<Model> $models
     */
    public function deleteModels(iterable $models): bool
    {
        if (empty($models)) {
            return true;
        }

        $groups = $this->splitModels($models);
        foreach ($groups as $modelName => $groupModels) {
            $table = $this->getTableByModelName($this->repository->getMainDb(), $modelName);
            if (!$table) {
                return false;
            }

            $ids = [];
            /** @var Model $model */
            foreach ($groupModels as $model) {
                if ($model->isNew()) {
                    continue;
                }

                $ids[] = $model->getId();
            }

            $result = $table->delete(['id' => $ids]);
            if (!$result) {
                return false;
            }
        }

        foreach ($models as $model) {
            $model->dropId();
        }

        return true;
    }

    /**
     * @param iterable<Model> $models
     */
    private function splitModels(iterable $models): array
    {
        $groups = [];
        foreach ($models as $model) {
            $modelName = $model->getModelName();
            if (!array_key_exists($modelName, $groups)) {
                $groups[$modelName] = [];
            }

            $groups[$modelName][] = $model;
        }

        return $groups;
    }

    private function getTableByModel(DB $db, Model $model): DbTable
    {
        return $this->getTableByModelName($db, $model->getModelName());
    }

    private function getTableByModelName(DB $db, string $modelName): DbTable
    {
        $nameConverter = $this->context->getNameConverter();
        $tableName = $nameConverter->getTableName($modelName);
        return $db->table($tableName);
    }

    private function splitManyToManyRelations(array $list): array
    {
        if (empty($list)) {
            return [[], []];
        }

        $nameConverter = $this->context->getNameConverter();
        $add = [];
        $delete = [];
        foreach ($list as $row) {
            /** @var Model $model0 */
            $model0 = $row[0];
            /** @var Model $model1 */
            $model1 = $row[2];
            /** @var ModelRelation $relation */
            $relation = $row[1];
            $key = ($model0->getModelName() < $model1->getModelName())
                ? ($model0->getId() . '_' . $model1->getId())
                : ($model1->getId() . '_' . $model0->getId());

            $tableName = $nameConverter->getManyToManyTableName(
                $model0->getModelName(),
                $relation->getName(),
                $model1->getModelName(),
                $relation->getRelatedAttributeName()
            );
            if ($row[3] == self::RELATION_FOR_ADD) {
                $add[$tableName][$key] = [$model0, $model1];
            } else {
                $delete[$tableName][$key] = [$model0, $model1];
            }
        }

        return [$add, $delete];
    }

    private function addManyToManyRelations(array $add): void
    {
        $nameConverter = $this->context->getNameConverter();
        foreach ($add as $tableName => $rows) {
            $name0 = reset($rows)[0]->getModelName();
            $name1 = reset($rows)[1]->getModelName();
            $fields = [
                $nameConverter->getRelationName($name0),
                $nameConverter->getRelationName($name1),
            ];
            $values = [];
            foreach ($rows as $row) {
                /** @var Model $model0 */
                $model0 = $row[0];
                /** @var Model $model1 */
                $model1 = $row[1];
                $pare = [0, 0];
                if ($model0->getModelName() == $name0) {
                    $pare[0] = $model0->getId();
                    $pare[1] = $model1->getId();
                } else {
                    $pare[1] = $model0->getId();
                    $pare[0] = $model1->getId();
                }
                $values[] = $pare;

                $model0->commitChanges();
                $model1->commitChanges();
            }

            $table = $this->repository->getMainDb()->table($tableName);
            $table->insert($fields, $values, false);
        }
    }

    private function deleteManyToManyRelations(array $delete): void
    {
        $nameConverter = $this->context->getNameConverter();
        foreach ($delete as $tableName => $rows) {
            //TODO оптимальнее?

            $table = $this->repository->getMainDb()->table($tableName);
            foreach ($rows as $row) {
                /** @var Model $model0 */
                $model0 = $row[0];
                /** @var Model $model1 */
                $model1 = $row[1];
                $table->delete([
                    $nameConverter->getRelationName($model0->getModelName()) => $model0->getId(),
                    $nameConverter->getRelationName($model1->getModelName()) => $model1->getId(),
                ]);

                $model0->commitChanges();
                $model1->commitChanges();
            }
        }
    }
}

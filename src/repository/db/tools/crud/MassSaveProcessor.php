<?php

namespace lx\model\repository\db\tools\crud;

use lx\ArrayHelper;
use lx\DbTable;
use lx\model\Model;
use lx\model\modelTools\ModelsCollection;
use lx\model\repository\db\Repository;
use lx\model\repository\db\tools\RepositoryContext;

class MassSaveProcessor
{
    private Repository $repository;
    private RepositoryContext $context;
    /** @var iterable<Model> */
    private $models;

    public function __construct(Repository $repository, array $models = [])
    {
        $this->repository = $repository;
        $this->context = $repository->getContext();
        $this->models = $models;
    }

    /**
     * @param iterable<Model> $models
     */
    public function setModels(array $models): void
    {
        $this->models = $models;
    }

    public function run(): bool
    {
        if (empty($this->models)) {
            return true;
        }

        $groups = $this->splitModels();
        foreach ($groups as $modelName => $group) {
            if (!$this->runInsert($modelName, $group['forInsert'])) {
                return false;
            }

            if (!$this->runUpdate($modelName, $group['forUpdate'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param iterable<Model> $list
     */
    public function analyzeModelsList(iterable $list): array
    {
        $forSave = [];
        $forRelationsUpdate = [];
        foreach ($list as $model) {
            $schema = $model->getSchema();
            $relationChanges = $model->getRelationChanges();

            $isForSave = false;
            if ($model->fieldsChanged()) {
                $isForSave = true;
                $forSave[] = $model;
            }

            foreach ($relationChanges as $relationName => $relationChange) {
                $relation = $schema->getRelation($relationName);

                if ($relation->isManyToMany()) {
                    foreach ($relationChange['added'] as $relModel) {
                        $forRelationsUpdate[] = [$model, $relation, $relModel, CrudProcessor::RELATION_FOR_ADD];
                    }
                    foreach ($relationChange['deleted'] as $relModel) {
                        $forRelationsUpdate[] = [$model, $relation, $relModel, CrudProcessor::RELATION_FOR_DELETE];
                    }
                } elseif (!$relation->isFkHolder()) {
                    foreach ($relationChange['added'] as $relModel) {
                        if (!in_array($relModel, $forSave)) {
                            $forSave[] = $relModel;
                        }
                    }
                    foreach ($relationChange['deleted'] as $relModel) {
                        if (!in_array($relModel, $forSave)) {
                            $forSave[] = $relModel;
                        }
                    }
                }

                if (!$isForSave && $relation->isFkHolder()) {
                    $isForSave = true;
                    $forSave[] = $model;
                }
            }
        }

        return [
            'forSave' => $forSave,
            'forRelationsUpdate' => $forRelationsUpdate,
        ];
    }

    /**
     * @param iterable<Model> $models
     */
    private function runInsert(string $modelName, iterable $models): bool
    {
        if (empty($models)) {
            return true;
        }

        $table = $this->getTable($modelName);
        if (!$table) {
            return false;
        }

        $rows = ModelFieldsConverter::toRepositoryForModels($this->context, $models);
        $rows = ArrayHelper::valuesStable($rows);

        $ids = (array)$table->insert($rows['keys'], $rows['rows']);
        $i = 0;
        foreach ($models as $model) {
            $model->setId($ids[$i++]);
            $model->commitChanges();
        }

        return true;
    }

    /**
     * @param iterable<Model> $models
     */
    private function runUpdate(string $modelName, iterable $models): bool
    {
        if (empty($models)) {
            return true;
        }

        $table = $this->getTable($modelName);
        if (!$table) {
            return false;
        }

        $rows = ModelFieldsConverter::toRepositoryForModels($this->context, $models);
        $result = $table->massUpdate($rows);
        if ($result) {
            foreach ($models as $model) {
                $model->commitChanges();
            }
        }

        return $result;
    }

    private function splitModels(): array
    {
        $groups = [];
        foreach ($this->models as $model) {
            if (!$model->isChanged()) {
                continue;
            }

            $modelName = $model->getModelName();
            if (!array_key_exists($modelName, $groups)) {
                $groups[$modelName] = [
                    'forInsert' => [],
                    'forUpdate' => [],
                ];
            }

            if ($model->isNew()) {
                $groups[$modelName]['forInsert'][] = $model;
            } else {
                $groups[$modelName]['forUpdate'][] = $model;
            }
        }

        return $groups;
    }

    private function getTable(string $modelName): ?DbTable
    {
        $nameConverter = $this->context->getNameConverter();
        $tableName = $nameConverter->getTableName($modelName);
        return $this->repository->getMainDb()->table($tableName);
    }
}

<?php

namespace lx\model\repository\db\tools\crud;

use lx\ArrayHelper;
use lx\model\Model;
use lx\model\modelTools\relationModelWrapper\RelationModelSetter;
use lx\model\repository\db\Repository;
use lx\model\schema\relation\ModelRelation;
use lx\model\schema\relation\RelationTypeEnum;

/**
 * Class RelatedLoader
 * @package lx\model\repository\db\tools\crud
 */
class RelatedLoader
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param Model $model
     * @param string $relationName
     * @return Model[]
     */
    public function loadForModel(Model $model, string $relationName): array
    {
        $schema = $model->getSchema();
        $relation = $schema->getRelation($relationName);

        switch ($relation->getType()) {
            case RelationTypeEnum::MANY_TO_MANY:
                return $this->runForManyToMany($model, $relation);
            case RelationTypeEnum::MANY_TO_ONE:
                return $this->runForManyToOne($model, $relation);
            case RelationTypeEnum::ONE_TO_MANY:
                return $this->runForOneToMany($model, $relation);
            case RelationTypeEnum::ONE_TO_ONE:
                return $this->runForOneToOne($model, $relation);
        }

        return [];
    }

    /**
     * @param Model $model
     * @param ModelRelation $relation
     * @return Model[]
     */
    private function runForManyToMany(Model $model, ModelRelation $relation): array
    {
        $modelId = $model->getId();
        if (!$modelId) {
            return [];
        }

        $nameConverter = $this->repository->getContext()->getNameConverter();
        $vsTableName = $nameConverter->getManyToManyTableName(
            $model->getModelName(),
            $relation->getName(),
            $relation->getRelatedModelName(),
            $relation->getRelatedAttributeName()
        );

        $modelIdColumn = $nameConverter->getRelationName($model->getModelName());
        $relIdColumn = $nameConverter->getRelationName($relation->getRelatedModelName());

        $ids = $this->repository->getReplicaDb()->query("
            SELECT $relIdColumn
            FROM $vsTableName
            WHERE $modelIdColumn = $modelId
        ");
        $ids = ArrayHelper::getColumn($ids, $relIdColumn);

        $relModels = $this->repository->findModelsByIds($relation->getRelatedModelName(), $ids);
        foreach ($relModels as $relModel) {
            $model->setRelated($relation->getName(), new RelationModelSetter($relModel));
            $relModel->setRelated($relation->getRelatedAttributeName(), new RelationModelSetter($model));
        }

        return $relModels;
    }

    /**
     * @param Model $model
     * @param ModelRelation $relation
     * @return Model[]
     */
    private function runForManyToOne(Model $model, ModelRelation $relation): array
    {
        $fk = $model->getRelatedKey($relation->getName());
        if ($fk === null) {
            return [];
        }

        $relModel = $model->getRepository()->findModel(
            $relation->getRelatedModelName(),
            $fk
        );

        $model->setRelated($relation->getName(), new RelationModelSetter($relModel));
        $relModel->setRelated($relation->getRelatedAttributeName(), new RelationModelSetter($model));
        return [$relModel];
    }

    /**
     * @param Model $model
     * @param ModelRelation $relation
     * @return Model[]
     */
    private function runForOneToMany(Model $model, ModelRelation $relation): array
    {
        $context = $this->repository->getContext();
        $nameConverter = $context->getNameConverter();
        $relModelName = $relation->getRelatedModelName();
        $relAttributeName = $nameConverter->getRelationName(
            $relModelName,
            $relation->getRelatedAttributeName()
        );

        $relModels = $this->repository->findModels(
            $relModelName,
            [
                $relAttributeName => $model->getId(),
            ]
        );

        foreach ($relModels as $relModel) {
            $model->setRelated($relation->getName(), new RelationModelSetter($relModel));
            $relModel->setRelated($relation->getRelatedAttributeName(), new RelationModelSetter($model));
        }

        return $relModels;
    }

    /**
     * @param Model $model
     * @param ModelRelation $relation
     * @return Model[]
     */
    private function runForOneToOne(Model $model, ModelRelation $relation): array
    {

        //TODO
    }
}

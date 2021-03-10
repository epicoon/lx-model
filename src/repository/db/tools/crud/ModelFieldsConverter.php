<?php

namespace lx\model\repository\db\tools\crud;

use lx\model\Model;
use lx\model\repository\db\tools\NameConverter;
use lx\model\repository\db\tools\RepositoryContext;
use lx\model\schema\ModelSchema;

/**
 * Class ModelFieldsConverter
 * @package lx\model\repository\db\tools\crud
 */
class ModelFieldsConverter
{
    public static function toRepositoryForModel(RepositoryContext $context, Model $model): array
    {
        $schema = $model->getSchema();
        return self::getColumnsForModel(
            $model,
            $context->getNameConverter(),
            $schema,
            $schema->getModelName()
        );
    }

    /**
     * @param RepositoryContext $context
     * @param Model[] $models
     * @return array
     */
    public static function toRepositoryForModels(RepositoryContext $context, array $models): array
    {
        $nameConverter = $context->getNameConverter();
        $schema = $models[0]->getSchema();
        $modelName = $schema->getModelName();

        $rows = [];
        foreach ($models as $model) {
            $columns = self::getColumnsForModel(
                $model,
                $nameConverter,
                $schema,
                $modelName
            );

            if (!$model->isNew()) {
                $columns['id'] = $model->getId();
            }

            $rows[] = $columns;
        }

        return $rows;
    }

    public static function fromRepository(RepositoryContext $context, string $modelName, array $columns): array
    {
        $nameConverter = $context->getNameConverter();
        $schema = $context->getModelManager()->getModelSchema($modelName);

        $fields = [];
        foreach ($columns as $columnName => $column) {
            if ($nameConverter->isRelationName($columnName)) {
                $relationName = $nameConverter->restoreRelationName($modelName, $columnName);
                $fields[$relationName] = $column;
                continue;
            }

            $fieldName = $nameConverter->restoreFieldName($modelName, $columnName);
            if (!$schema->hasField($fieldName)) {
                continue;
            }

            $type = $schema->getField($fieldName)->getType();
            $fields[$fieldName] = $type->valueFromRepository($column);
        }

        return $fields;
    }

    public static function toRepositoryForCondition(
        RepositoryContext $context,
        string $modelName,
        array $condition
    ): array
    {
        $nameConverter = $context->getNameConverter();
        $schema = $context->getModelManager()->getModelSchema($modelName);

        $result = [];
        foreach ($condition as $key => $value) {
            if ($schema->hasField($key)) {
                $key = $nameConverter->getFieldName($modelName, $key);
            }

            $result[$key] = $value;
        }

        return $result;
    }

    private static function getColumnsForModel(
        Model $model,
        NameConverter $nameConverter,
        ModelSchema $schema,
        string $modelName
    ) : array
    {
        $columns = [];
        foreach ($model->getFields() as $fieldName => $value) {
            $key = $nameConverter->getFieldName($modelName, $fieldName);
            $type = $schema->getField($fieldName)->getType();
            $columns[$key] = $type->valueToRepository($value);
        }
        foreach ($schema->getRelations() as $relationName => $relation) {
            if ($relation->isFkHolder()) {
                if ($model->isRelationLoaded($relationName)) {
                    $columnName = $nameConverter->getRelationName($modelName, $relationName);
                    $value = $model->getRelatedKey($relationName);
                    $columns[$columnName] = $value;
                }
            }
        }
        return $columns;
    }
}
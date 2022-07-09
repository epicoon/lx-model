<?php

namespace lx\model\repository\db\tools\crud;

use lx\model\Model;
use lx\model\repository\db\tools\NameConverter;
use lx\model\repository\db\tools\RepositoryContext;
use lx\model\schema\ModelSchema;

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
     * @param iterable<Model> $models
     */
    public static function toRepositoryForModels(RepositoryContext $context, iterable $models): array
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

            $field = $schema->getField($fieldName);
            $type = $field->getType();
            $fields[$fieldName] = $type->valueFromRepository($field->getRawValue($column));
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
            $field = $schema->getField($fieldName);
            $type = $field->getType();
            $columns[$key] = $type->valueToRepository($field->getRawValue($value));
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

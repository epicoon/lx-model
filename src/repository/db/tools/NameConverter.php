<?php

namespace lx\model\repository\db\tools;

use lx\StringHelper;

class NameConverter
{
    const RELATION_PREFIX = 'fk_';

    private RepositoryContext $context;

    public function __construct(RepositoryContext $context)
    {
        $this->context = $context;
    }

    public function getServiceSchemaName(): string
    {
        return $this->context->getRepository()->isSingle()
            ? ''
            : str_replace('/', '_', StringHelper::camelToSnake($this->context->getService()->name)) . '.';
    }

    public function getTableName(string $modelName, bool $withServiceSchema = true): ?string
    {
        if ($modelName == '') {
            return null;
        }

        //TODO

        $modelName = str_replace('\\', '__', $modelName);
        $modelName = str_replace('/', '__', $modelName);
        $modelName = StringHelper::camelToSnake($modelName);
        // avoid reserved words like "user"
        if ($modelName == 'user') {
            $modelName = 'users';
        }

        return $withServiceSchema
            ? $this->getServiceSchemaName() . $modelName
            : $modelName;
    }

    public function getFieldName(?string $modelName, string $fieldName): string
    {
        //TODO

        return StringHelper::camelToSnake($fieldName);
    }

    public function getRelationName(string $modelName, ?string $fieldName = null): string
    {
        if ($fieldName === null) {
            return self::RELATION_PREFIX . $this->getTableName($modelName, false);
        }

        //TODO
        return self::RELATION_PREFIX . $this->getFieldName($modelName, $fieldName);
    }

    public function restoreModelName(string $tableName): string
    {
        $serviceSchema = $this->getServiceSchemaName();
        if ($serviceSchema != '') {
            $tableName = preg_replace('/^' . $serviceSchema . '/', '', $tableName);
        }

        $modelNamesMap = array_keys($this->context->getModelManager()->getModelClassesMap());
        foreach ($modelNamesMap as $name) {
            if ($this->getTableName($name, false) == $tableName) {
                return $name;
            }
        }

        return StringHelper::snakeToCamel($tableName);
    }

    public function restoreFieldName(string $modelName, string $fieldName): string
    {
        return StringHelper::snakeToCamel($fieldName);
    }

    public function restoreRelationName(string $modelName, string $relationName): string
    {
        $fieldName = preg_replace('/^' . self::RELATION_PREFIX . '/', '', $relationName);
        return $this->restoreFieldName($modelName, $fieldName);
    }

    public function isRelationName(string $name): bool
    {
        return (bool)preg_match('/^' . self::RELATION_PREFIX . '/', $name);
    }

    public function getManyToManyTableName(
        string $modelName1,
        string $attributeName1,
        string $modelName2,
        string $attributeName2
    ): string
    {
        if ($modelName1 > $modelName2) {
            $temp = $modelName1;
            $modelName1 = $modelName2;
            $modelName2 = $temp;
            $temp = $attributeName1;
            $attributeName1 = $attributeName2;
            $attributeName2 = $temp;
        }

        $tabSchemaName = $this->getServiceSchemaName();
        return $tabSchemaName . '_rel__'
            . $this->getTableName($modelName1, false)
            . '__'
            . $this->getFieldName($modelName1, $attributeName1)
            . '__'
            . $this->getTableName($modelName2, false)
            . '__'
            . $this->getFieldName($modelName2, $attributeName2);
    }
}

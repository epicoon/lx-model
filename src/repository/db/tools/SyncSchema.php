<?php

namespace lx\model\repository\db\tools;

use lx\DbTableField;
use lx\DbTableSchema;
use lx\model\Model;
use lx\model\repository\db\migrationExecutor\actions\relation\LifeCycleRelationAction;
use lx\model\schema\ModelSchema;
use lx\model\schema\field\type\PhpTypeEnum;
use lx\model\schema\field\ModelField;
use lx\model\schema\relation\ModelRelation;
use lx\model\schema\relation\RelationTypeEnum;

class SyncSchema
{
    private static array $anonymousSchemas = [];

    private RepositoryContext $context;
    private string $modelName;
    private ?ModelSchema $modelSchema;
    private ?DbTableSchema $dbSchema;

    public function __construct(RepositoryContext $context, string $modelName)
    {
        $this->context = $context;
        $this->modelName = $modelName;
        $this->modelSchema = null;
        $this->dbSchema = null;
    }

    public static function createAnonymousModel(RepositoryContext $context, array $config): ?Model
    {
        $modelName = $config['modelName'] ?? null;
        if (!$modelName) {
            $tableName = $config['tableName'] ?? null;
            if (!$tableName) {
                return null;
            }

            $nameConverter = $context->getNameConverter();
            $modelName = $nameConverter->restoreModelName($tableName);
        }

        if (!$modelName) {
            return null;
        }

        $modelFields = $config['modelFields'] ?? null;
        if (!$modelFields) {
            $tableFields = $config['tableFields'] ?? null;
            if ($tableFields) {
                $modelFields = [];
                $nameConverter = $context->getNameConverter();
                foreach ($tableFields as $name => $value) {
                    $modelFields[$nameConverter->restoreFieldName($modelName, $name)] = $value;
                }
            }
        }

        if (!$modelFields) {
            $modelFields = [];
        }

        $service = $context->getService();
        $key = $service->name . '::' . $modelName;
        if (array_key_exists($key, self::$anonymousSchemas)) {
            return Model::createAnonymousModel($service, self::$anonymousSchemas[$key], $modelFields);
        }

        $instance = new self($context, $modelName);
        self::$anonymousSchemas[$key] = $instance->restoreModelSchema()->getModelSchema();
        return Model::createAnonymousModel($service, self::$anonymousSchemas[$key], $modelFields);
    }

    public function getModelSchema(): ?ModelSchema
    {
        if ($this->modelSchema === null) {
            if ($this->dbSchema === null) {
                $this->loadModelSchema();
            } else {
                $this->restoreModelSchema();
            }
        }

        return $this->modelSchema;
    }

    public function getDbSchema(): DbTableSchema
    {
        if ($this->dbSchema === null) {
            if ($this->modelSchema === null) {
                $this->loadDbSchema();
            } else {
                $this->restoreDbSchema();
            }
        }

        return $this->dbSchema;
    }

    public function reset(): SyncSchema
    {
        $this->resetDbSchema();
        $this->resetModelSchema();
        return $this;
    }

    public function resetModelSchema(): SyncSchema
    {
        $this->modelSchema = null;
        return $this;
    }

    public function resetDbSchema(): SyncSchema
    {
        $this->dbSchema = null;
        return $this;
    }

    public function setDbSchemaByConfig(array $config): SyncSchema
    {
        $schema = [
            'name' => $config['tableName'],
        ];

        $fields = [];
        foreach (($config['fields'] ?? []) as $fieldName => $fieldDefinition) {
            $fieldDefinition['name'] = $fieldName;
            $fields[$fieldName] = $fieldDefinition;
        }

        if (!empty($fields)) {
            $schema['fields'] = $fields;
        }

        $db = $this->context->getRepository()->getMainDb();
        $this->dbSchema = DbTableSchema::createByConfig($db, $schema);
        return $this;
    }

    public function setModelSchemaByConfig(array $config): SyncSchema
    {
        $this->modelSchema = ModelSchema::createFromArray($config, $this->context->getService());
        return $this;
    }

    public function loadModelSchema(): SyncSchema
    {
        $this->modelSchema = SchemaBuffer::getModelSchema($this->modelName)
            ?? $this->context->getModelManager()->getModelSchema($this->modelName);
        return $this;
    }

    public function loadDbSchema(): SyncSchema
    {
        $tableName = $this->context->getNameConverter()->getTableName($this->modelName);
        $db = $this->context->getRepository()->getMainDb();
        $this->dbSchema = $db->getTableSchema($tableName);
        return $this;
    }

    public function restoreDbSchema(): SyncSchema
    {
        $modelSchema = $this->modelSchema;
        if (!$modelSchema) {
            $this->loadModelSchema();
            $modelSchema = $this->modelSchema;
        }

        $schema = [];

        $modelName = $modelSchema->getModelName();
        $nameConverter = $this->context->getNameConverter();
        $schema['name'] = $nameConverter->getTableName($modelName);

        $fields = [];
        foreach ($modelSchema->getFields() as $fieldName => $field) {
            $definition = $this->fieldToDbDefinition($field);
            $fields[$definition['name']] = $definition;
        }

        if (!empty($fields)) {
            $schema['fields'] = $fields;
        }

        $db = $this->context->getRepository()->getMainDb();
        $this->dbSchema = DbTableSchema::createByConfig($db, $schema);

        $this->dbSchema->addField([
            'name' => 'id',
            'type' => DbTableField::TYPE_INTEGER,
            'pk' => true,
            'nullable' => false,
        ]);
        $this->dbSchema->setPK('id');

        return $this;
    }

    public function restoreModelSchema(): SyncSchema
    {
        $dbSchema = $this->dbSchema;
        if (!$dbSchema) {
            $this->loadDbSchema();
            if (!$this->dbSchema) {
                return $this;
            }
            $dbSchema = $this->dbSchema;
        }

        $schemaArray = [
            'name' => $this->modelName,
            'fields' => [],
            'relations' => [],
        ];

        $sysTablesProvider = new SysTablesProvider($this->context);
        $typesTable = $sysTablesProvider->getTable(SysTablesProvider::SCHEMA_CUSTOM_TYPES_TABLE);
        $customTypes = $typesTable->select(['column_name', 'type'], [
            'table_name' => $this->dbSchema->getName(),
        ]);
        $customTypesMap = [];
        foreach ($customTypes as $row) {
            $customTypesMap[$row['column_name']] = $row['type'];
        }

        $nameConverter = $this->context->getNameConverter();
        $currentSchema = $this->context->getModelManager()->getModelSchema($this->modelName);
        foreach ($dbSchema->getFields() as $fieldName => $field) {
            if ($fieldName == 'id') {
                continue;
            }

            if ($field->isFk()) {
                $fkName = $nameConverter->restoreRelationName($this->modelName, $field->getName());
                $fkConstraintName = $field->getDefinition()['fk']['name'];
                $fkConstraintArr = explode('__', $fkConstraintName);
                $relModel = $nameConverter->restoreModelName($fkConstraintArr[3]);
                $relAttribute = (count($fkConstraintArr) == 5)
                    ? $nameConverter->restoreFieldName($relModel, $fkConstraintArr[4])
                    : null;

                $definition = [
                    'type' => ($fkConstraintArr[0] == LifeCycleRelationAction::CONSTRAINT_PREFIX_ONE_TO_ONE)
                        ? RelationTypeEnum::ONE_TO_ONE
                        : RelationTypeEnum::MANY_TO_ONE,
                    'relatedEntityName' => $relModel,
                    'relatedAttributeName' => $relAttribute,
                ];
                if ($definition['type'] == RelationTypeEnum::ONE_TO_ONE) {
                    $definition['fkHost'] = true;
                }

                $schemaArray['relations'][$fkName] = $definition;
                continue;
            }

            $codeFieldName = $nameConverter->restoreFieldName($this->modelName, $fieldName);
            $codeField = $currentSchema->getField($codeFieldName);


            $type = null;
            if (array_key_exists($fieldName, $customTypesMap)) {
                $type = $customTypesMap[$fieldName];
            }
            if (!$type) {
                $type = $this->restoreType($field->getType());
            }

            $definition = [
                'type' => $type,
                'required' => !$field->isNullable(),
            ];
            $size = $field->getSize();
            if ($size !== null) {
                $definition['size'] = $size;
            }
            $default = $field->getDefault();
            if ($default !== null) {
                $definition['default'] = $default;
            }

            if ($codeField) {
                if ($codeField->isReadOnly()) {
                    $definition['readonly'] = true;
                }

                //TODO constraints
            }

            $schemaArray['fields'][$codeFieldName] = $definition;
        }

        // Relations from another tables
        $contrForeignKeys = $this->dbSchema->getContrForeignKeysInfo();
        foreach ($contrForeignKeys as $key) {
            $fkConstraintArr = explode('__', $key['name']);
            // MANY_TO_MANY
            if (preg_match('/^' . $nameConverter->getServiceSchemaName() . '_rel__/', $key['table'])) {
                $relModel = $nameConverter->restoreModelName($fkConstraintArr[3]);
                if ($relModel == $this->modelName) {
                    $relModel = $nameConverter->restoreModelName($fkConstraintArr[5]);
                    $relAttribute = $nameConverter->restoreFieldName($relModel, $fkConstraintArr[6]);
                    $attribute = $nameConverter->restoreFieldName($this->modelName, $fkConstraintArr[4]);
                } else {
                    $relAttribute = $nameConverter->restoreFieldName($relModel, $fkConstraintArr[4]);
                    $attribute = $nameConverter->restoreFieldName($this->modelName, $fkConstraintArr[6]);
                }

                $schemaArray['relations'][$attribute] = [
                    'type' => RelationTypeEnum::MANY_TO_MANY,
                    'relatedEntityName' => $relModel,
                    'relatedAttributeName' => $relAttribute,
                ];
            // ONE_TO_(ONE|MANY)
            } else {
                if (count($$fkConstraintArr) == 4) {
                    continue;
                }

                $attribute = $nameConverter->restoreFieldName($this->modelName, $fkConstraintArr[4]);
                $relModel = $nameConverter->restoreModelName($fkConstraintArr[1]);
                $relAttribute = $nameConverter->restoreFieldName($relModel, $fkConstraintArr[2]);
                $type = ($fkConstraintArr[0] == LifeCycleRelationAction::CONSTRAINT_PREFIX_ONE_TO_ONE)
                    ? RelationTypeEnum::ONE_TO_ONE
                    : RelationTypeEnum::ONE_TO_MANY;
                $schemaArray['relations'][$attribute] = [
                    'type' => $type,
                    'relatedEntityName' => $relModel,
                    'relatedAttributeName' => $relAttribute,
                ];
            }
        }

        $schemaArray['className'] = $this->context->getModelManager()->getModelClassName($this->modelName);
        $this->modelSchema = ModelSchema::createFromArray($schemaArray, $this->context->getService());

        return $this;
    }

    public function fieldToBasicDefinitionArray(ModelField $field): array
    {
        $result = $field->toArray();
        unset($result['name']);
        unset($result['readonly']);
        //TODO unset($result['constraints']);

        if (array_key_exists('default', $result) && $result['default'] === null) {
            unset($result['default']);
        }

        return $result;
    }

    public function relationToBasicDefinitionArray(ModelRelation $relation): array
    {
        $result = [
            'type' => $relation->getType(),
            'relModel' => $relation->getRelatedModelName(),
            'relAttribute' => $relation->getRelatedAttributeName(),
        ];
        if ($relation->isOneToOne() && $relation->isFkHolder()) {
            $result['fkHost'] = true;
        }
        return $result;
    }

    public function fieldToDbDefinition(ModelField $field): array
    {
        $result = $this->fieldToBasicDefinitionArray($field);
        $result['nullable'] = array_key_exists('required', $result)
            ? !$result['required']
            : true;
        unset($result['required']);

        $result['type'] = $this->convertType($field);

        $nameConverter = $this->context->getNameConverter();
        $result['name'] = $nameConverter->getFieldName($this->modelName, $field->getName());

        return $result;
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function convertType(ModelField $field): string
    {
        switch ($field->getPhpType()) {
            case PhpTypeEnum::INTEGER:
                return DbTableField::TYPE_INTEGER;

            case PhpTypeEnum::FLOAT:
                return DbTableField::TYPE_FLOAT;

            case PhpTypeEnum::BOOLEAN:
                return DbTableField::TYPE_BOOLEAN;

            case PhpTypeEnum::STRING:
            case PhpTypeEnum::ARRAY:
                return DbTableField::TYPE_STRING;

            //TODO можно object сериализовать
        }

        return DbTableField::TYPE_STRING;
    }

    private function restoreType(string $dbType): string
    {
        switch ($dbType) {
            case DbTableField::TYPE_INTEGER:
                return PhpTypeEnum::INTEGER;

            case DbTableField::TYPE_FLOAT:
                return PhpTypeEnum::FLOAT;

            case DbTableField::TYPE_BOOLEAN:
                return PhpTypeEnum::BOOLEAN;

            case DbTableField::TYPE_STRING:
            case DbTableField::TYPE_DECIMAL:
            case DbTableField::TYPE_TIMESTAMP:
                return PhpTypeEnum::STRING;
        }

        return PhpTypeEnum::STRING;
    }
}

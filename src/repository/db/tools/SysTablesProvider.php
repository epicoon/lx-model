<?php

namespace lx\model\repository\db\tools;

use lx\DbTable;
use lx\DbTableEditor;
use lx\DbTableField;
use lx\DbTableSchema;

class SysTablesProvider
{
    const MIGRATIONS_TABLE = 'lx.migrations';
    const SCHEMA_CUSTOM_TYPES_TABLE = 'lx.schema_custom_types';
    const MIGRATIONS_META_DATA = 'lx.migrations_meta_data';

    private RepositoryContext $context;

    public function __construct(RepositoryContext $context)
    {
        $this->context = $context;
    }

    public function isTableExist(string $name): bool
    {
        $db = $this->context->getRepository()->getMainDb();
        return $db->tableExists($name);
    }

    public function getTable(string $name): DbTable
    {
        $db = $this->context->getRepository()->getMainDb();
        if (!$db->tableExists($name)) {
            $this->createTable($name);

        }

        return $db->getTable($name);
    }

    private function createTable(string $name): void
    {
        switch ($name) {
            case self::MIGRATIONS_TABLE:
                $this->createMigrationsTable();
                break;
            case self::SCHEMA_CUSTOM_TYPES_TABLE:
                $this->createTypesTable();
                break;
            case self::MIGRATIONS_META_DATA:
                $this->createMigrationsMetaDataTable();
                break;
        }
    }

    private function createMigrationsTable(): void
    {
        DbTableEditor::createTableFromConfig($this->context->getRepository()->getMainDb(), [
            'name' => self::MIGRATIONS_TABLE,
            'fields' => [
                'service' => [
                    'type' => DbTableField::TYPE_STRING,
                    'nullable' => false,
                ],
                'version' => [
                    'type' => DbTableField::TYPE_STRING,
                    'nullable' => false,
                ],
                'created_at' => [
                    'type' => DbTableField::TYPE_TIMESTAMP,
                    'nullable' => false,
                ],
            ],
        ]);
    }

    private function createTypesTable(): void
    {
        DbTableEditor::createTableFromConfig($this->context->getRepository()->getMainDb(), [
            'name' => self::SCHEMA_CUSTOM_TYPES_TABLE,
            'fields' => [
                'table_name' => [
                    'type' => DbTableField::TYPE_STRING,
                    'nullable' => false,
                ],
                'column_name' => [
                    'type' => DbTableField::TYPE_STRING,
                    'nullable' => false,
                ],
                'type' => [
                    'type' => DbTableField::TYPE_STRING,
                    'nullable' => false,
                ],
            ],
        ]);
    }

    private function createMigrationsMetaDataTable(): void
    {
        DbTableEditor::createTableFromConfig($this->context->getRepository()->getMainDb(), [
            'name' => self::MIGRATIONS_META_DATA,
            'fields' => [
                'version' => [
                    'type' => DbTableField::TYPE_STRING,
                    'nullable' => false,
                ],
                'model_name' => [
                    'type' => DbTableField::TYPE_STRING,
                    'nullable' => false,
                ],
                'model_id' => [
                    'type' => DbTableField::TYPE_INTEGER,
                    'nullable' => false,
                ],
            ],
        ]);
    }
}

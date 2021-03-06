<?php

namespace lx\model\repository\db\tools;

use lx\DbTable;
use lx\DbTableBuilder;
use lx\DbTableField;
use lx\DbTableSchema;

/**
 * Class SysTablesProvider
 * @package lx\model\repository\db\tools
 */
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

        return $db->table($name);
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

    private function createMigrationsTable()
    {
        $db = $this->context->getRepository()->getMainDb();
        $dbSchema = DbTableSchema::createByConfig($db, [
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
                    'type' => DbTableField::TYPE_STRING, //TODO datetime
                    'nullable' => false,
                ],
            ],
        ]);
        $builder = new DbTableBuilder($dbSchema);
        $builder->createTable();
    }

    private function createTypesTable()
    {
        $db = $this->context->getRepository()->getMainDb();
        $dbSchema = DbTableSchema::createByConfig($db, [
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
        $builder = new DbTableBuilder($dbSchema);
        $builder->createTable();
    }

    private function createMigrationsMetaDataTable()
    {
        $db = $this->context->getRepository()->getMainDb();
        $dbSchema = DbTableSchema::createByConfig($db, [
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
        $builder = new DbTableBuilder($dbSchema);
        $builder->createTable();
    }
}

<?php

namespace lx\model\repository\db\migrationExecutor;

use lx\model\repository\db\tools\RepositoryContext;
use lx\model\repository\db\tools\SysTablesProvider;
use lx\model\schema\field\ModelField;

/**
 * Class CustomTypesRecorder
 * @package lx\model\repository\db\migrationExecutor
 */
class CustomTypesRecorder
{
    private RepositoryContext $context;

    public function __construct(RepositoryContext $context)
    {
        $this->context = $context;
    }

    public function onDropTable(string $tableName): void
    {
        $sysTableProvider = new SysTablesProvider($this->context);
        $table = $sysTableProvider->getTable(SysTablesProvider::SCHEMA_CUSTOM_TYPES_TABLE);
        $table->delete([
            'table_name' => $tableName
        ]);
    }

    public function onDropColumn(string $tableName, string $columnName): void
    {
        $sysTableProvider = new SysTablesProvider($this->context);
        $table = $sysTableProvider->getTable(SysTablesProvider::SCHEMA_CUSTOM_TYPES_TABLE);
        $table->delete([
            'table_name' => $tableName,
            'column_name' => $columnName,
        ]);
    }

    /**
     * @param string $tableName
     * @param ModelField[] $fields
     */
    public function onAdded(string $tableName, array $fields): void
    {
        $list = $this->getList($fields);
        if (empty($list)) {
            return;
        }

        $nameConverter = $this->context->getNameConverter();
        $sysTableProvider = new SysTablesProvider($this->context);
        $table = $sysTableProvider->getTable(SysTablesProvider::SCHEMA_CUSTOM_TYPES_TABLE);
        foreach ($list as $row) {
            $columnName = $nameConverter->getFieldName(null, $row['field']);
            $res = $table->select('*', [
                'table_name' => $tableName,
                'column_name' => $columnName,
            ]);
            if (empty($res)) {
                $table->insert(['table_name', 'column_name', 'type'], [
                    [
                        $tableName, $columnName, $row['type']
                    ]
                ], false);
            } else {
                $table->update(['type' => $row['type']], [
                    'table_name' => $tableName,
                    'column_name' => $columnName,
                ]);
            }
        }
    }

    /**
     * @param ModelField[] $fields
     * @return array
     */
    private function getList(array $fields): array
    {
        $list = [];
        foreach ($fields as $field) {
            $type = $field->getType();
            if (!$type->isCustom()) {
                continue;
            }

            $list[] = [
                'field' => $field->getName(),
                'type' => $type->getTypeName(),
            ];
        }

        return $list;
    }
}

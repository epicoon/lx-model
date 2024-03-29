<?php

namespace lx\model\repository\db\migrationExecutor;

use lx\model\repository\db\tools\RepositoryContext;
use lx\model\repository\db\tools\SysTablesProvider;
use lx\model\schema\field\ModelField;

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
     * @param array<ModelField> $fields
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
                $table->insert(['table_name', 'column_name', 'type', 'definition'], [
                    [
                        $tableName, $columnName, $row['type'], $row['definition']
                    ]
                ], false);
            } else {
                $table->update(['type' => $row['type'], 'definition' => $row['definition']], [
                    'table_name' => $tableName,
                    'column_name' => $columnName,
                ]);
            }
        }
    }

    /**
     * @param array<ModelField> $fields
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
                'definition' => $field->getDefinition()->toString(),
            ];
        }

        return $list;
    }
}

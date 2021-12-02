<?php

namespace lx\model\repository\db\migrationExecutor\actions\relation;

use lx\DbTableEditor;
use lx\DbTableSchema;
use lx\model\repository\db\migrationExecutor\actions\BaseMigrationAction;
use lx\model\repository\db\migrationExecutor\actions\MigrationActionTypeEnum;
use lx\model\repository\db\tools\SysTablesProvider;

class DelRelationAction extends LifeCycleRelationAction
{
    public function inverse(): BaseMigrationAction
    {
        $data = $this->data;
        $data['type'] = MigrationActionTypeEnum::ADD_RELATION;
        return BaseMigrationAction::create($this->context, $data);
    }

    protected function executeToOne(): void
    {
        $nameConverter = $this->context->getNameConverter();
        $tableName = $nameConverter->getTableName($this->data['modelName']);
        $fieldName = $nameConverter->getRelationName($this->modelName, $this->attributeName);

        $editor = new DbTableEditor();
        $editor->loadTableSchema($this->context->getRepository()->getMainDb(), $tableName);
        $result = $editor->delField($fieldName);
        if (!$result) {
            $this->addError('Can not delete a field');
            return;
        }
        $fkName = $this->dropFkNotice($this->modelName, $this->attributeName);
    }

    protected function executeManyToMany(): void
    {
        $nameConverter = $this->context->getNameConverter();
        $tableName = $nameConverter->getManyToManyTableName(
            $this->modelName,
            $this->attributeName,
            $this->relModelName,
            $this->relAttributeName
        );

        $editor = new DbTableEditor();
        $editor->loadTableSchema($this->context->getRepository()->getMainDb(), $tableName);
        $editor->dropTable();
        $this->dropFkNotice($this->modelName, $this->attributeName);
        $this->dropFkNotice($this->relModelName, $this->relAttributeName);
    }
    
    private function dropFkNotice(string $model, string $field): void
    {
        $nameConverter = $this->context->getNameConverter();
        $table = str_replace('.', '_', $nameConverter->getTableName($model));
        $field = $nameConverter->getFieldName($model, $field);
        $fkName = "fk__{$table}__{$field}";
        $sysTablesProvider = new SysTablesProvider($this->context);
        $sysTablesProvider->getTable(SysTablesProvider::RELATIONS_TABLE)->delete([
            'fk_name' => $fkName,
        ]);
    }
}

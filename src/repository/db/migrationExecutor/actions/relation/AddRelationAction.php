<?php

namespace lx\model\repository\db\migrationExecutor\actions\relation;

use lx\DbTableEditor;
use lx\DbTableField;
use lx\DbTableSchema;
use lx\model\repository\db\migrationExecutor\actions\BaseMigrationAction;
use lx\model\repository\db\migrationExecutor\actions\MigrationActionTypeEnum;
use lx\model\repository\db\tools\SyncSchema;

class AddRelationAction extends LifeCycleRelationAction
{
    public function inverse(): BaseMigrationAction
    {
        $data = $this->data;
        $data['type'] = MigrationActionTypeEnum::DEL_RELATION;
        return BaseMigrationAction::create($this->context, $data);
    }

    protected function executeToOne(): void
    {
        $nameConverter = $this->context->getNameConverter();
        $syncSchema = new SyncSchema($this->context, $this->modelName);
        $dbSchema = $syncSchema->loadDbSchema()->getDbSchema();

        $editor = new DbTableEditor();
        $editor->setTableSchema($dbSchema);
        $tableKey = str_replace('.', '_', $nameConverter->getTableName($this->modelName));
        $fieldName = $nameConverter->getFieldName($this->modelName, $this->attributeName);
        $relationName = $nameConverter->getRelationName($this->modelName, $this->attributeName);
        $relTableKey = str_replace(
            '.', '_', $nameConverter->getTableName($this->relModelName, false)
        );
        $relFieldName = $this->relAttributeName
            ? $nameConverter->getFieldName($this->relModelName, $this->relAttributeName)
            : null;
        $result = $editor->addField([
            'name' => $relationName,
            'type' => DbTableField::TYPE_INTEGER,
            'fk' => [
                'table' => $nameConverter->getTableName($this->relModelName),
                'field' => 'id',
                'name' => $relFieldName
                    ? "{$this->getFkPrefix()}__{$tableKey}__{$fieldName}__{$relTableKey}__{$relFieldName}"
                    : "{$this->getFkPrefix()}__{$tableKey}__{$fieldName}__{$relTableKey}",
            ]
        ]);
        if (!$result) {
            $this->addError('Can not add the relation');
        }
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
        
        DbTableEditor::createTableFromConfig($this->context->getRepository()->getMainDb(), [
            'name' => $tableName,
            'fields' => [
                $nameConverter->getRelationName($this->modelName) => [
                    'type' => DbTableField::TYPE_INTEGER,
                    'nullable' => false,
                    'fk' => [
                        'table' => $nameConverter->getTableName($this->modelName),
                        'field' => 'id',
                    ]
                ],
                $nameConverter->getRelationName($this->relModelName) => [
                    'type' => DbTableField::TYPE_INTEGER,
                    'nullable' => false,
                    'fk' => [
                        'table' => $nameConverter->getTableName($this->relModelName),
                        'field' => 'id',
                    ]
                ],
            ]
        ]);
    }
}

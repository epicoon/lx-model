<?php

namespace lx\model\repository\db\migrationExecutor\actions\relation;

use lx\DbTableEditor;
use lx\DbTableField;
use lx\DbTableSchema;
use lx\model\repository\db\migrationExecutor\actions\BaseMigrationAction;
use lx\model\repository\db\migrationExecutor\actions\MigrationActionTypeEnum;
use lx\model\repository\db\tools\SyncSchema;
use lx\model\repository\db\tools\SysTablesProvider;
use lx\model\schema\relation\RelationTypeEnum;

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

        $fkName = $this->noteFk($this->modelName, $this->attributeName, $this->relModelName, $this->relAttributeName);
        $relationName = $nameConverter->getRelationName($this->modelName, $this->attributeName);
        $result = $editor->addField([
            'name' => $relationName,
            'type' => DbTableField::TYPE_INTEGER,
            'fk' => [
                'table' => $nameConverter->getTableName($this->relModelName),
                'field' => 'id',
                'name' => $fkName,
            ],
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

        $fkName = $this->noteFk($this->modelName, $this->attributeName, $this->relModelName, $this->relAttributeName);
        $relFkName = $this->noteFk($this->relModelName, $this->relAttributeName, $this->modelName, $this->attributeName);

        DbTableEditor::createTableFromConfig($this->context->getRepository()->getMainDb(), [
            'name' => $tableName,
            'fields' => [
                $nameConverter->getRelationName($this->modelName) => [
                    'type' => DbTableField::TYPE_INTEGER,
                    'nullable' => false,
                    'fk' => [
                        'name' => $relFkName,
                        'table' => $nameConverter->getTableName($this->modelName),
                        'field' => 'id',
                    ]
                ],
                $nameConverter->getRelationName($this->relModelName) => [
                    'type' => DbTableField::TYPE_INTEGER,
                    'nullable' => false,
                    'fk' => [
                        'name' => $fkName,
                        'table' => $nameConverter->getTableName($this->relModelName),
                        'field' => 'id',
                    ]
                ],
            ]
        ]);
    }

    private function noteFk(string $model, string $field, string $relModel, string $relField): string
    {
        $nameConverter = $this->context->getNameConverter();
        $table = str_replace('.', '_', $nameConverter->getTableName($model));
        $fieldKey = $nameConverter->getFieldName($model, $field);
        $fkName = "fk__{$table}__{$fieldKey}";
        $sysTablesProvider = new SysTablesProvider($this->context);
        $sysTablesProvider->getTable(SysTablesProvider::RELATIONS_TABLE)->insert([
            'fk_name' => $fkName,
            'type' => $this->relationType,
            'service' => $this->context->getService()->name,
            'home_model' => $model,
            'home_field' => $field,
            'rel_model' => $relModel,
            'rel_field' => $relField,
        ]);
        return $fkName;
    }
}

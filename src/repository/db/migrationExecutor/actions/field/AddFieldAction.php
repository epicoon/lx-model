<?php

namespace lx\model\repository\db\migrationExecutor\actions\field;

use lx\DbTableEditor;
use lx\model\repository\db\migrationExecutor\actions\BaseMigrationAction;
use lx\model\repository\db\migrationExecutor\actions\MigrationActionTypeEnum;
use lx\model\repository\db\migrationExecutor\CustomTypesRecorder;
use lx\model\repository\db\tools\SyncSchema;
use lx\model\schema\field\ModelField;

class AddFieldAction extends BaseMigrationAction
{
    public function inverse(): BaseMigrationAction
    {
        $data = $this->data;
        $data['type'] = MigrationActionTypeEnum::DEL_FIELD;
        return BaseMigrationAction::create($this->context, $data);
    }

    protected function execute(): void
    {
        $syncSchema = new SyncSchema($this->context, $this->data['modelName']);

        $dbSchema = $syncSchema->loadDbSchema()->getDbSchema();
        $editor = new DbTableEditor();
        $editor->setTableSchema($dbSchema);

        $modelSchema = $syncSchema->loadModelSchema()->getModelSchema();
        $field = new ModelField($modelSchema, $this->data['fieldName'], $this->data['definition']);
        $dbFieldDefinition = $syncSchema->fieldToDbDefinition($field);

        $result = $editor->addField($dbFieldDefinition);
        if (!$result) {
            $this->addError('Can not add the field');
            return;
        }

        $customTypeRecorder = new CustomTypesRecorder($this->context);
        $customTypeRecorder->onAdded($syncSchema->getDbSchema()->getName(), [$field]);
    }
}

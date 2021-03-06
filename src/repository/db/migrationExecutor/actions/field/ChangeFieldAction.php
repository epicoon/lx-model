<?php

namespace lx\model\repository\db\migrationExecutor\actions\field;

use lx\DbTableBuilder;
use lx\model\repository\db\migrationExecutor\actions\BaseMigrationAction;
use lx\model\repository\db\migrationExecutor\CustomTypesRecorder;
use lx\model\repository\db\tools\SyncSchema;
use lx\model\schema\field\ModelField;

/**
 * Class ChangeFieldAction
 * @package lx\model\repository\db\migrationExecutor\actions
 */
class ChangeFieldAction extends BaseMigrationAction
{
    public function inverse(): BaseMigrationAction
    {
        $old = $this->data['oldDefinition'];
        $this->data['oldDefinition'] = $this->data['newDefinition'];
        $this->data['newDefinition'] = $old;
        return $this;
    }

    protected function execute(): void
    {
        $syncSchema = new SyncSchema($this->context, $this->data['modelName']);

        $dbSchema = $syncSchema->loadDbSchema()->getDbSchema();
        $builder = new DbTableBuilder($dbSchema);

        $modelSchema = $syncSchema->loadModelSchema()->getModelSchema();
        $field = new ModelField($modelSchema, $this->data['fieldName'], $this->data['newDefinition']);
        $dbFieldDefinition = $syncSchema->fieldToDbDefinition($field);

        $result = $builder->changeField($dbFieldDefinition);
        if (!$result) {
            $this->addError('Can not change field');
            return;
        }

        $customTypeRecorder = new CustomTypesRecorder($this->context);
        $customTypeRecorder->onAdded($syncSchema->getDbSchema()->getName(), [$field]);
    }
}

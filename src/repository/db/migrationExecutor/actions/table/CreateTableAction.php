<?php

namespace lx\model\repository\db\migrationExecutor\actions\table;

use lx\DbTableEditor;
use lx\DbTableField;
use lx\model\repository\db\migrationExecutor\actions\BaseMigrationAction;
use lx\model\repository\db\migrationExecutor\actions\MigrationActionTypeEnum;
use lx\model\repository\db\migrationExecutor\CustomTypesRecorder;
use lx\model\repository\db\tools\SyncSchema;

class CreateTableAction extends BaseMigrationAction
{
    public function inverse(): BaseMigrationAction
    {
        $data = $this->data;
        $data['type'] = MigrationActionTypeEnum::DROP_TABLE;
        return BaseMigrationAction::create($this->context, $data);
    }

    protected function execute(): void
    {
        if (!isset($this->data['schema']) || !isset($this->data['schema']['name'])) {
            $this->report->addToMigrationErrors([
                'migration' => $this->data['migrationName'],
                'error' => 'Wrong schema configuration',
            ]);
            return;
        }

        $syncSchema = new SyncSchema($this->context, $this->data['schema']['name']);
        $syncSchema->setModelSchemaByConfig($this->data['schema']);
        $tableSchema = $syncSchema->getDbSchema();
        if (!$tableSchema) {
            $this->report->addToMigrationErrors([
                'migration' => $this->data['migrationName'],
                'error' => 'Wrong schema configuration',
            ]);
            return;
        }

        $editor = new DbTableEditor();
        $editor->setTableSchema($tableSchema);
        if (!$editor->createTable()) {
            $this->addError('Wrong schema configuration');
            return;
        }

        $modelSchema = $syncSchema->getModelSchema();
        $customTypesRecorder = new CustomTypesRecorder($this->context);
        $customTypesRecorder->onAdded($tableSchema->getName(), $modelSchema->getFields());
    }
}

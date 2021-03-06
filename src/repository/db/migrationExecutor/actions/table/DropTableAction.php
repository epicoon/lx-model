<?php

namespace lx\model\repository\db\migrationExecutor\actions\table;

use lx\model\repository\db\migrationExecutor\actions\BaseMigrationAction;
use lx\model\repository\db\migrationExecutor\actions\MigrationActionTypeEnum;
use lx\model\repository\db\migrationExecutor\CustomTypesRecorder;

/**
 * Class DropTableAction
 * @package lx\model\repository\db\migrationExecutor\actions\table
 */
class DropTableAction extends BaseMigrationAction
{
    public function inverse(): BaseMigrationAction
    {
        $data = $this->data;
        $data['type'] = MigrationActionTypeEnum::CREATE_TABLE;
        return BaseMigrationAction::create($this->context, $data);
    }

    protected function execute(): void
    {
        $action = $this->data;
        $tableName = $this->context->getNameConverter()->getTableName($action['schema']['name'] ?? '');
        if (!$tableName) {
            $this->report->addToMigrationErrors([
                'migration' => $this->migration->getName(),
                'error' => 'Wrong schema configuration',
            ]);
            return;
        }

        $db = $this->repository->getMainDb();
        $result = $db->dropTable($tableName);
        if (!$result) {
            $this->addError('Wrong schema configuration');
            return;
        }

        $customTypesRecorder = new CustomTypesRecorder($this->context);
        $customTypesRecorder->onDropTable($tableName);
    }
}

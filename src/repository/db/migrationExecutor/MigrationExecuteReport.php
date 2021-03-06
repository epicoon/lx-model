<?php

namespace lx\model\repository\db\migrationExecutor;

use lx\CascadeReport;
use lx\model\repository\ReportInterface;

/**
 * Class MigrationExecuteReport
 * @package lx\model\repository\db\migrationExecutor
 *
 * @method addToAppliedMigrations(string $name)
 * @method addToMigrationsWrongSequence(string $name)
 * @method addToMigrationErrors(array $data)
 */
class MigrationExecuteReport extends CascadeReport implements ReportInterface
{
    protected function getDataComponents(): array
    {
        return [
            'appliedMigrations' => CascadeReport::COMPONENT_LIST,
            'migrationsWrongSequence' => CascadeReport::COMPONENT_LIST,
            'migrationErrors' => CascadeReport::COMPONENT_LIST,
        ];
    }

    public function hasErrors(): bool
    {
        $data = $this->toArray();
        return (!empty($data['migrationErrors']));
    }
}

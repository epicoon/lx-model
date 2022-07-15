<?php

namespace lx\model\repository\db\migrationBuilder;

use lx\CascadeReport;
use lx\model\repository\ReportInterface;

/**
 * @method addToNewMigrations(string $name)
 */
class MigrationBuildReport extends CascadeReport implements ReportInterface
{
    protected function getDataComponents(): array
    {
        return [
            'newMigrations' => CascadeReport::COMPONENT_LIST,
        ];
    }
}

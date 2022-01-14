<?php

namespace lx\model\repository\db\comparator;

use lx\CascadeReport;
use lx\model\repository\ReportInterface;

/**
 * Class CompareRepositoryReport
 * @package lx\model\repository\db\comparator
 *
 * @method addToWrongModelNames(string $name)
 * @method addListToWrongModelNames(array $names)
 * @method addToUnappliedMigrations(string $name)
 * @method addListToUnappliedMigrations(array $names)
 * @method addToModelsNeedTable(string $name)
 * @method addListToModelsNeedTable(array $names)
 * @method addToModelsChanged(string $name, array $changes)
 * @method addListToModelsChanged(array $changes)
 * @method addToErrors(string $error)
 */
class CompareRepositoryReport extends CascadeReport implements ReportInterface
{
    protected function getDataComponents(): array
    {
        return [
            'wrongModelNames' => CascadeReport::COMPONENT_LIST,
            'unappliedMigrations' => CascadeReport::COMPONENT_LIST,
            'modelsNeedTable' => CascadeReport::COMPONENT_LIST,
            'modelsChanged' => CascadeReport::COMPONENT_DICT,
            'errors' => CascadeReport::COMPONENT_LIST,
        ];
    }
}

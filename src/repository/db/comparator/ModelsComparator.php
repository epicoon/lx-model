<?php

namespace lx\model\repository\db\comparator;

use lx\model\repository\db\Repository;
use lx\model\repository\db\tools\RepositoryContext;
use lx\model\repository\db\tools\MigrationConductor;
use lx\model\managerTools\ModelsContext;

class ModelsComparator
{
    private ModelsContext $context;
    private Repository $repository;
    private CompareRepositoryReport $report;

    public function __construct(RepositoryContext $context)
    {
        $this->context = $context;
        $this->repository = $context->getRepository();
        $this->report = new CompareRepositoryReport();
    }

    public function run(?array $modelNames = null): CompareRepositoryReport
    {
        $migrationsConductor = new MigrationConductor($this->context);
        $unappliedMigrations = $migrationsConductor->getUnappliedList();
        if (!empty($unappliedMigrations)) {
            $this->report->addListToUnappliedMigrations($unappliedMigrations);
            return $this->report;
        }

        $conductor = $this->context->getConductor();

        if (is_array($modelNames)) {
            $validatedNames = $conductor->validateModelNames($modelNames);
            $wrongNames = array_diff($modelNames, $validatedNames);
            $this->report->addListToWrongModelNames($wrongNames);
        } else {
            $validatedNames = $conductor->getAllModelNames();
        }

        foreach ($validatedNames as $modelName) {
            $comparator = new ModelComparator($this->context, $modelName);
            $report = $comparator->run();
            $this->report->add($report);
        }

        return $this->report;
    }
}

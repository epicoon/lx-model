<?php

namespace lx\model\repository\db\migrationExecutor;

use Exception;
use lx\model\repository\db\Repository;
use lx\model\repository\db\migrationExecutor\actions\BaseMigrationAction;
use lx\model\repository\db\tools\RepositoryContext;
use lx\model\repository\db\tools\Migration;
use lx\model\repository\db\tools\MigrationConductor;

/**
 * Class MigrationExecutor
 * @package lx\model\repository\db\migrationExecutor
 */
class MigrationExecutor
{
    private RepositoryContext $context;
    private Repository $repository;
    private array $unappliedList;
    private array $appliedList;
    private MigrationExecuteReport $report;

    public function __construct(RepositoryContext $context)
    {
        $this->context = $context;
        $this->repository = $context->getRepository();
        $this->unappliedList = [];
        $this->appliedList = [];
        $this->report = new MigrationExecuteReport();
    }

    public function run(?int $count = null, bool $rollback = false): MigrationExecuteReport
    {
        $conductor = new MigrationConductor($this->context);

        $migrations = $conductor->getMigrations();
        if ($rollback) {
            $migrations = array_reverse($migrations);
        }
        $inActual = false;
        $counter = 0;
        $success = true;

        $db = $this->context->getRepository()->getMainDb();
        $db->transactionBegin();
        foreach ($migrations as $migration) {
            if (!$rollback && $migration->isApplied()) {
                if ($inActual) {
                    $this->report->addToMigrationsWrongSequence($migration->getName());
                }
                continue;
            }
            if ($rollback && !$migration->isApplied()) {
                if ($inActual) {
                    $this->report->addToMigrationsWrongSequence($migration->getName());
                }
                continue;
            }

            $inActual = true;
            if (!$this->applyMigration($migration, $rollback)) {
                $success = false;
                break;
            }

            $this->report->addToAppliedMigrations($migration->getName());
            $counter++;
            if ($count !== null && $counter >= $count) {
                break;
            }
        }

        if ($success) {
            if (!$conductor->actualize($this->unappliedList, $this->appliedList)) {
                $success = false;
            }
        }

        if ($success) {
            $db->transactionCommit();
        } else {
            $db->transactionRollback();
        }

        return $this->report;
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function applyMigration(Migration $migration, bool $rollback = false): bool
    {
        $actions = $this->normalizeActions($migration);
        if (!$actions) {
            return false;
        }

        if ($rollback) {
            $actionsTemp = array_reverse($actions);
            $actions = [];
            foreach ($actionsTemp as $action) {
                $actions[] = $action->inverse();
            }
        }

        try {
            foreach ($actions as $action) {
                $report = $action->run();
                $this->report->add($report);
                if ($report->hasErrors()) {
                    return false;
                }
            }
        } catch (Exception $exception) {
            $this->report->addToMigrationErrors([
                'migration' => $migration->getName(),
                'error' => $exception->getMessage(),
            ]);
            return false;
        }

        if ($rollback) {
            $this->unappliedList[] = $migration->getVersion();
        } else {
            $this->appliedList[] = $migration->getVersion();
        }
        return true;
    }

    /**
     * @param Migration $migration
     * @return BaseMigrationAction[]|false
     */
    private function normalizeActions(Migration $migration)
    {
        $data = $migration->get();

        $result = [];

        $actions = $data['actions'] ?? [];
        foreach ($actions as $actionData) {
            $actionData['migration'] = $migration;
            $action = $this->createAction($actionData);
            if (!$action) {
                return false;
            }

            $result[] = $action;
        }

        $modelChanges = $data['modelChanges'] ?? [];
        foreach ($modelChanges as $modelChangeData) {
            $modelName = $modelChangeData['modelName'] ?? null;
            if (!$modelName) {
                $this->report->addToMigrationErrors([
                    'migration' => $migration->getName(),
                    'error' => 'Model name is missed',
                ]);
                return false;
            }

            $actions = $modelChangeData['actions'] ?? [];
            if (empty($actions)) {
                $this->report->addToMigrationErrors([
                    'migration' => $migration->getName(),
                    'error' => "Actions list for model '$modelName' is missed",
                ]);
                return false;
            }

            foreach ($actions as $actionData) {
                $actionData['modelName'] = $modelName;
                $actionData['migration'] = $migration;
                $action = $this->createAction($actionData);
                if (!$action) {
                    return false;
                }

                $result[] = $action;
            }
        }

        return $result;
    }

    private function createAction(array $data): ?BaseMigrationAction
    {
        try {
            if (($data['type'] ?? '') == 'TODO') {
                /** @var Migration $migration */
                $migration = $data['migration'];
                $this->report->addToMigrationErrors([
                    'migration' => $migration->getName(),
                    'error' => 'This migration require implementation',
                ]);
                return null;
            }

            return BaseMigrationAction::create($this->context, $data);
        } catch (Exception $exception) {
            /** @var Migration $migration */
            $migration = $data['migration'];
            $this->report->addToMigrationErrors([
                'migration' => $migration->getName(),
                'error' => $exception->getMessage(),
            ]);
            return null;
        }
    }
}

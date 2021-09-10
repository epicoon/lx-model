<?php

namespace lx\model\cli;

use lx;
use lx\model\repository\MigrationReporter;
use lx\model\ModelManager;
use lx\ServiceCliExecutor;

class ModelUpdate extends ServiceCliExecutor
{
    const LEVEL_FULL = 'full';
    const LEVEL_MEDIATOR = 'mediator';
    const LEVEL_GEN_MIGRATION = 'gen-migration';
    const LEVEL_RUN_MIGRATION = 'run-migration';

    private array $levels;

    public function run(): void
    {
        $this->defineService();
        $models = $this->processor->getArg('model');
        if ($models) {
            if (!$this->service) {
                $this->processor->outln('Models are belonged to services. You have to point a service.');
                return;
            }

            $models = (array)$models;
        }

        $level = $this->processor->getArg('level') ?? self::LEVEL_FULL;
        if ($level == self::LEVEL_FULL) {
            $this->levels = [
                self::LEVEL_MEDIATOR,
                self::LEVEL_GEN_MIGRATION,
                self::LEVEL_RUN_MIGRATION,
            ];
        } else {
            $this->levels = (array)$level;
        }

        if ($this->service) {
            $this->runForService($this->service->name, $models);
            return;
        }

        $this->runForAll();
    }

    private function runForAll(): void
    {
        $servicesData = MigrationReporter::getServicesData();
        foreach ($servicesData as $serviceData) {
            $report = $serviceData['report'];
            if (empty($report['unappliedMigrations'])
                && empty($report['modelsNeedTable'])
                && empty($report['modelsChanged'])
                && empty($report['modelsNeedUpdate'])
            ) {
                continue;
            }

            $this->processor->outln(
                '*** Service: ' . $serviceData['serviceName'] . ', category: ' . $serviceData['serviceCategory'],
                ['decor' => 'b']
            );
            $this->runForService($serviceData['serviceName']);
            $this->processor->outln('***', ['decor' => 'b']);
        }
    }

    private function runForService(string $serviceName, ?array $models = null): void
    {
        $service = lx::$app->getService($serviceName);
        if (!$service) {
            $this->processor->outln("Service $serviceName not found");
            return;
        }

        /** @var ModelManager $modelManager */
        $modelManager = $service->modelManager;

        if ($modelManager->getRepository()->hasUnappliedMigrations()) {
            if (!$this->isLevelAllowed(self::LEVEL_RUN_MIGRATION)) {
                $this->processor->outln("Service $serviceName has unapplied migrations. You have to apply them before update models.");
                return;
            }

            $this->runMigrations($modelManager);
        }

        $modelDiffReport = $modelManager->compareModels($models);
        if (is_array($models)) {
            $wrongModels = $modelDiffReport->extract('wrongModelNames');
            if (!empty($wrongModels)) {
                $this->processor->outln('* The following models not found:', ['decor' => 'b']);
                foreach ($wrongModels as $name) {
                    $this->processor->outln('>>> ' . $name);
                }
                $models = array_diff($models, $wrongModels);
            }
        }

        if (!$modelDiffReport->isEmpty()) {
            if (!$this->isLevelAllowed(self::LEVEL_MEDIATOR)) {
                $this->processor->outln("Service $serviceName has changes in the model schemas. You have to refresh mediators.");
                return;
            }

            $this->refreshModels($modelManager, $models);
        }

        if (!$this->isLevelAllowed(self::LEVEL_GEN_MIGRATION)) {
            return;
        }

        $this->genMigrations($modelManager);

        if (!$this->isLevelAllowed(self::LEVEL_RUN_MIGRATION)) {
            return;
        }

        if ($modelManager->getRepository()->hasUnappliedMigrations()) {
            $this->runMigrations($modelManager);
        }
    }

    private function refreshModels(ModelManager $modelManager, ?array $models): void
    {
        $report = $modelManager->refreshModels($models)->toArray();

        if (!empty($report['wrongModelNames'])) {
            $this->processor->outln('* Wrong model names:', ['decor' => 'b']);
            foreach ($report['wrongModelNames'] as $name) {
                $this->processor->outln('>>> ' . $name);
            }
            return;
        }

        if (!empty($report['errors'])) {
            $this->processor->outln('* Errors:', ['decor' => 'b']);
            foreach ($report['errors'] as $error) {
                $this->processor->outln('>>> ' . $error);
            }
            return;
        }

        if (!empty($report['modelsCreated'])) {
            $this->processor->outln('* The following models have been created:', ['decor' => 'b']);
            foreach ($report['modelsCreated'] as $modelName => $modelClassName) {
                $this->processor->outln(">>> model: $modelName, class: $modelClassName");
            }
        }

        if (!empty($report['mediatorCreated'])) {
            $this->processor->outln('* The following model mediators have been created:', ['decor' => 'b']);
            foreach ($report['mediatorCreated'] as $modelName => $mediatorName) {
                $this->processor->outln(">>> model: $modelName, mediator: $mediatorName");
            }
        }

        if (!empty($report['mediatorUpdated'])) {
            $this->processor->outln('* The following model mediators have been updated:', ['decor' => 'b']);
            foreach ($report['mediatorUpdated'] as $modelName => $mediatorName) {
                $this->processor->outln(">>> model: $modelName, mediator: $mediatorName");
            }
        }
    }

    private function genMigrations(ModelManager $modelManager): void
    {
        $report = $modelManager->refreshMigrations();
        if ($report->isEmpty()) {
            return;
        }

        $report = $report->toArray();
        $this->processor->outln('* Migrations have been created:', ['decor' => 'b']);
        foreach ($report['newMigrations'] as $migrationName) {
            $this->processor->outln(">>> $migrationName");
        }
    }

    private function runMigrations(ModelManager $modelManager): void
    {
        $report = $modelManager->runMigrations()->toArray();
        $appliedMigrations = $report['appliedMigrations'] ?? [];
        if (empty($appliedMigrations)) {
            $this->processor->outln('* Migrations havn\'t been applied', ['decor' => 'b']);
        } else {
            $this->processor->outln('* Migrations have been applied:', ['decor' => 'b']);
            foreach ($appliedMigrations as $migrationName) {
                $this->processor->outln(">>> $migrationName");
            }
        }

        $errors = $report['migrationErrors'] ?? [];
        if (!empty($errors)) {
            $this->processor->outln('* Migration errors:', ['decor' => 'b']);
            foreach ($errors as $error) {
                $this->processor->outln("> migration: {$error['service']} - {$error['migration']}");
                $this->processor->outln(">>> message: {$error['error']}");
                $dbError = $error['dbError'] ?? null;
                if ($dbError) {
                    $this->processor->outln(">>> details: {$dbError}");
                }
            }
        }
    }

    private function isLevelAllowed(string $level): bool
    {
        return in_array($level, $this->levels);
    }
}

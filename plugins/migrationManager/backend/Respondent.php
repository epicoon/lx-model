<?php

namespace lx\model\plugins\migrationManager\backend;

use lx;
use lx\Respondent as lxRespondent;
use lx\model\ModelManager;
use lx\model\repository\MigrationReporter;

class Respondent extends lxRespondent
{
    public function getServicesData(): array
    {
        return MigrationReporter::getServicesData();
    }

    public function renewServiceData(string $serviceName): array
    {
        return MigrationReporter::getServiceData($serviceName);
    }

    public function createMigrations(string $serviceName): array
    {
        $service = lx::$app->getService($serviceName);
        if (!$service) {
            return [];
        }

        /** @var ModelManager $modelManager */
        $modelManager = $service->modelManager;
        $reportModels = $modelManager->refreshModels();
        $reportMigrations = $modelManager->refreshMigrations();

        return [
            'actionReport' => array_merge($reportModels->toArray(), $reportMigrations->toArray()),
            'serviceState' => MigrationReporter::getServiceData($serviceName)
        ];
    }

    public function runMigrations(string $serviceName, ?int $count = null): array
    {
        $service = lx::$app->getService($serviceName);
        if (!$service) {
            return [];
        }

        /** @var ModelManager $modelManager */
        $modelManager = $service->modelManager;
        $report = $modelManager->runMigrations($count);

        return [
            'actionReport' => $report->toArray(),
            'serviceState' => MigrationReporter::getServiceData($serviceName)
        ];
    }

    public function rollbackMigrations(string $serviceName, ?int $count = null): array
    {
        $service = lx::$app->getService($serviceName);
        if (!$service) {
            return [];
        }

        /** @var ModelManager $modelManager */
        $modelManager = $service->modelManager;
        $report = $modelManager->rollbackMigrations($count);

        return [
            'actionReport' => $report->toArray(),
            'serviceState' => MigrationReporter::getServiceData($serviceName)
        ];
    }

    public function getServiceMigrations(string $serviceName): array
    {
        $service = lx::$app->getService($serviceName);
        if (!$service) {
            return [];
        }

        /** @var ModelManager $modelManager */
        $modelManager = $service->modelManager;
        $migrations = $modelManager->getRepository()->getMigrations();
        $migrations = array_reverse($migrations);
        $result = [];
        foreach ($migrations as $migration) {
            $result[] = [
                'name' => $migration->getName(),
                'isApplied' => $migration->isApplied(),
            ];
        }

        return $result;
    }

    public function getMigrationText(string $serviceName, string $migrationName): string
    {
        $service = lx::$app->getService($serviceName);
        if (!$service) {
            return 'error';
        }

        /** @var ModelManager $modelManager */
        $modelManager = $service->modelManager;
        $migration = $modelManager->getRepository()->getMigration($migrationName);
        $file = $migration->getFile();

        return $file->getText();
    }
}

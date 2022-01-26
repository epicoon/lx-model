<?php

namespace lx\model\plugins\migrationManager\server;

use lx;
use lx\Respondent as lxRespondent;
use lx\model\ModelManager;
use lx\model\repository\MigrationReporter;
use lx\ResponseInterface;

class Respondent extends lxRespondent
{
    public function getServicesData(): ResponseInterface
    {
        return $this->prepareResponse(MigrationReporter::getServicesData());
    }

    public function renewServiceData(string $serviceName): ResponseInterface
    {
        return $this->prepareResponse(MigrationReporter::getServiceData($serviceName));
    }

    public function createMigrations(string $serviceName): ResponseInterface
    {
        $service = lx::$app->getService($serviceName);
        if (!$service) {
            return $this->prepareResponse([]);
        }

        /** @var ModelManager $modelManager */
        $modelManager = $service->modelManager;
        $reportModels = $modelManager->refreshModels();
        $reportMigrations = $modelManager->refreshMigrations();

        return $this->prepareResponse([
            'actionReport' => array_merge($reportModels->toArray(), $reportMigrations->toArray()),
            'serviceState' => MigrationReporter::getServiceData($serviceName)
        ]);
    }

    public function runMigrations(string $serviceName, ?int $count = null): ResponseInterface
    {
        $service = lx::$app->getService($serviceName);
        if (!$service) {
            return $this->prepareResponse([]);
        }

        /** @var ModelManager $modelManager */
        $modelManager = $service->modelManager;
        $report = $modelManager->runMigrations($count);

        return $this->prepareResponse([
            'actionReport' => $report->toArray(),
            'serviceState' => MigrationReporter::getServiceData($serviceName)
        ]);
    }

    public function rollbackMigrations(string $serviceName, ?int $count = null): ResponseInterface
    {
        $service = lx::$app->getService($serviceName);
        if (!$service) {
            return $this->prepareResponse([]);
        }

        /** @var ModelManager $modelManager */
        $modelManager = $service->modelManager;
        $report = $modelManager->rollbackMigrations($count);

        return $this->prepareResponse([
            'actionReport' => $report->toArray(),
            'serviceState' => MigrationReporter::getServiceData($serviceName)
        ]);
    }

    public function getServiceMigrations(string $serviceName): ResponseInterface
    {
        $service = lx::$app->getService($serviceName);
        if (!$service) {
            return $this->prepareResponse([]);
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

        return $this->prepareResponse($result);
    }

    public function getMigrationText(string $serviceName, string $migrationName): ResponseInterface
    {
        $service = lx::$app->getService($serviceName);
        if (!$service) {
            return $this->prepareResponse('error');
        }

        /** @var ModelManager $modelManager */
        $modelManager = $service->modelManager;
        $migration = $modelManager->getRepository()->getMigration($migrationName);
        $text = $migration ? $migration->getFile()->getText() : '';

        return $this->prepareResponse($text);
    }
}

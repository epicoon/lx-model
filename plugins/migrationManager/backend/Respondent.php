<?php

namespace lx\model\plugins\migrationManager\backend;

use lx;
use lx\Respondent as lxRespondent;
use lx\model\ModelManager;
use lx\model\repository\MigrationReporter;

/**
 * Class Respondent
 * @package lx\model\plugins\migrationManager\backend
 */
class Respondent extends lxRespondent
{
    /**
     * @return array
     */
    public function getServicesData()
    {
        return MigrationReporter::getServicesData();
    }

    /**
     * @param string $serviceName
     * @return array
     */
    public function renewServiceData($serviceName)
    {
        return MigrationReporter::getServiceData($serviceName);
    }

    /**
     * @param string $serviceName
     * @return array
     */
    public function createMigrations($serviceName)
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

    /**
     * @param string $serviceName
     * @param int$count
     * @return array
     */
    public function runMigrations($serviceName, $count = null)
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

    /**
     * @param string $serviceName
     * @param int $count
     * @return array
     */
    public function rollbackMigrations($serviceName, $count = null)
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

    /**
     * @param string $serviceName
     * @return array
     */
    public function getServiceMigrations($serviceName)
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

    /**
     * @param string $serviceName
     * @param string $migrationName
     * @return string
     */
    public function getMigrationText($serviceName, $migrationName)
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

//    /**
//     * @param string $serviceName
//     * @return array|null
//     */
//    private function getServiceData($serviceName)
//    {
//        $service = lx::$app->getService($serviceName);
//        if (!$service) {
//            return null;
//        }
//
//        /** @var ModelManager $modelManager */
//        $modelManager = $service->modelManager;
//        $models = $modelManager->getModelNames();
//        if (empty($models)) {
//            return null;
//        }
//
//        $report = $modelManager->compareRepository()->toArray();
//        $modelsCompareReport = $modelManager->compareModels()->toArray();
//        $report['modelsNeedUpdate'] = $modelsCompareReport['modelsNeedUpdate'] ?? [];
//
//        return [
//            'serviceName' => $serviceName,
//            'serviceCategory' => $service->getCategory(),
//            'report' => $report,
//        ];
//    }
}

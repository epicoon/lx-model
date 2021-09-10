<?php

namespace lx\model\repository;

use lx;
use lx\model\ModelManager;
use lx\PackageBrowser;

class MigrationReporter
{
    public static function getServicesData(): array
    {
        $services = PackageBrowser::getServicesList();

        $servicesData = [];
        foreach ($services as $serviceName => $service) {
            $data = self::getServiceData($serviceName);
            if ($data) {
                $servicesData[] = $data;
            }
        }

        return $servicesData;
    }

    public static function getServiceData(string $serviceName, ?array $modelNames = null): ?array
    {
        $service = lx::$app->getService($serviceName);
        if (!$service) {
            return null;
        }

        /** @var ModelManager $modelManager */
        $modelManager = $service->modelManager;
        $models = $modelManager->getModelNames();
        if (empty($models)) {
            return null;
        }
        
        $report = $modelManager->compareRepository($modelNames)->toArray();
        $modelsCompareReport = $modelManager->compareModels($modelNames)->toArray();
        $report['modelsNeedUpdate'] = $modelsCompareReport['modelsNeedUpdate'] ?? [];

        return [
            'serviceName' => $serviceName,
            'serviceCategory' => $service->getCategory(),
            'report' => $report,
        ];
    }
}

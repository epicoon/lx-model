<?php

namespace lx\model\repository;

use lx;
use lx\model\ModelManager;
use lx\ServiceBrowser;

class MigrationReporter
{
    public static function getServicesData(): array
    {
        $services = ServiceBrowser::getServicesList();

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
        if (!$modelManager) {
            return [
                'serviceName' => $serviceName,
                'serviceCategory' => $service->getCategory(),
                'report' => [
                    'errors' => 'ModelManager does not implemented',
                ],
            ];
        }

        $modelNames = $modelManager->getModelNames();
        if (empty($modelNames)) {
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

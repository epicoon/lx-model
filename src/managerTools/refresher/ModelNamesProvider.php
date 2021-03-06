<?php

namespace lx\model\managerTools\refresher;

use lx\model\managerTools\ModelsContext;

/**
 * Class ModelNamesProvider
 * @package lx\model\managerTools\refresher
 */
class ModelNamesProvider
{
    private ModelsContext $context;
    private array $map;

    public function __construct(ModelsContext $context)
    {
        $this->context = $context;

        $conductor = $this->context->getConductor();
        $modelNames = $conductor->getAllModelNames();
        $this->map = [];
        foreach ($modelNames as $modelName) {
            $this->map[$modelName] = $this->defineNamesForModel($modelName);
        }
    }

    public function getShortModelName(string $modelName): string
    {
        return $this->map[$modelName]['shortModelName'] ?? '';
    }

    public function getModelNamespace(string $modelName): string
    {
        return $this->map[$modelName]['modelNamespace'] ?? '';
    }

    public function getMediatorName(string $modelName): string
    {
        return $this->map[$modelName]['mediatorName'] ?? '';
    }

    public function getMediatorNamespace(string $modelName): string
    {
        return $this->map[$modelName]['mediatorNamespace'] ?? '';
    }

    private function defineNamesForModel($fullModelName): array
    {
        $psr = $this->context->getService()->getConfig('autoload.psr-4');
        if (!$psr) {
            throw new \Exception('Models need PSR-4 autoload rules');
        }

        $modelsPath = $this->context->getModelsPath();
        $defaultNamespace = null;
        $namespaceForModels = null;
        $mediatorNamespace = null;
        foreach ($psr as $namespace => $paths) {
            foreach ((array)$paths as $path) {
                if ($path == '') {
                    $defaultNamespace = $namespace;
                    continue;
                }

                if (strpos($modelsPath, $path) === 0) {
                    $namespaceForModels = $namespace;
                }

                if (strpos('.system', $path) === 0) {
                    $mediatorNamespace = $namespace;
                }
            }
        }

        if (!$mediatorNamespace) {
            throw new \Exception('Models need PSR-4 autoload rules for ".system" classes');
        }

        $namespaceForModels = $namespaceForModels ?? $defaultNamespace;
        if (!$namespaceForModels) {
            throw new \Exception('Not found namespace for models');
        }

        $relModelsNamespace = str_replace('/', '\\', $modelsPath);

        $modelNameArray = explode('\\', $fullModelName);
        if (count($modelNameArray) == 1) {
            $modelName = $fullModelName;
            $modelNamespace = $namespaceForModels . $relModelsNamespace;
            $mediatorName = $modelName . 'Mediator';
            $mediatorNamespace = $mediatorNamespace . $relModelsNamespace;
        } else {
            $modelName = array_pop($modelNameArray);
            $namespaceTail = implode('\\', $modelNameArray);
            $modelNamespace = $namespaceForModels . $relModelsNamespace . $namespaceTail;
            $mediatorName = $modelName . 'Mediator';
            $mediatorNamespace = $mediatorNamespace . $relModelsNamespace . $namespaceTail;
        }

        return [
            'shortModelName' => $modelName,
            'modelNamespace' => $modelNamespace,
            'mediatorName' => $mediatorName,
            'mediatorNamespace' => $mediatorNamespace,
        ];
    }
}

<?php

namespace lx\model\managerTools;

use lx\model\ModelManager;
use lx\Service;

class ModelsContext
{
    private Service $service;
    private ModelManager $modelManager;
    private string $modelSchemasPath;
    private string $modelsPath;
    private string $modelSchemasExtension;
    private string $migrationExtension;
    private ?Conductor $conductor;

    public function __construct(
        Service $service,
        ModelManager $modelManager,
        string $modelSchemasPath,
        string $modelsPath,
        string $modelSchemasExtension,
        string $migrationExtension
    )
    {
        $this->service = $service;
        $this->modelManager = $modelManager;
        $this->modelSchemasPath = $modelSchemasPath;
        $this->modelsPath = $modelsPath;
        $this->modelSchemasExtension = $modelSchemasExtension;
        $this->migrationExtension = $migrationExtension;
        $this->conductor = null;
    }

    public function getService(): Service
    {
        return $this->service;
    }

    public function getModelManager(): ModelManager
    {
        return $this->modelManager;
    }

    public function getModelSchemasPath(): string
    {
        return $this->modelSchemasPath;
    }

    public function getModelsPath(): string
    {
        return $this->modelsPath;
    }

    public function getModelSchemasExtension(): string
    {
        return $this->modelSchemasExtension;
    }

    public function getMigrationExtension(): string
    {
        return $this->migrationExtension;
    }

    public function getConductor(): Conductor
    {
        if (!$this->conductor) {
            $this->conductor = new Conductor($this);
        }

        return $this->conductor;
    }
}

<?php

namespace lx\model;

use lx\model\repository\RepositoryInterface;
use lx\Service;
use lx\FusionComponentInterface;
use lx\FusionComponentTrait;
use lx\ModelManagerInterface;
use lx\model\repository\ReportInterface;
use lx\model\repository\db\Repository;
use lx\model\managerTools\ModelsContext;
use lx\model\managerTools\refresher\ModelsRefresher;
use lx\model\managerTools\refresher\RefreshReport;
use lx\model\schema\ModelSchema;

/**
 * @property-read RepositoryInterface $repository
 */
class ModelManager implements ModelManagerInterface, FusionComponentInterface
{
    use FusionComponentTrait;

    const DEFAULT_MODEL_SCHEMAS_PATH = 'schemas/models';
    const DEFAULT_MODELS_PATH = 'models';
    const DEFAULT_MODEL_SCHEMAS_EXTENSION = 'yaml';
    const DEFAULT_MIGRATION_EXTENSION = 'yaml';

    private ModelsContext $context;
    private ?array $modelClassesMap;

    protected function afterObjectConstruct(iterable $config): void
    {
        $this->context = new ModelsContext(
            $this->getService(),
            $this,
            $config['schemas'] ?? self::DEFAULT_MODEL_SCHEMAS_PATH,
            $config['models'] ?? self::DEFAULT_MODELS_PATH,
            $config['modelSchemasExtension'] ?? self::DEFAULT_MODEL_SCHEMAS_EXTENSION,
            $config['migrationExtension'] ?? self::DEFAULT_MIGRATION_EXTENSION
        );

        $this->modelClassesMap = null;

        if ($this->repository) {
            $this->repository->setContext($this->context);
            $this->repository->setConfig($config['repositoryConfig'] ?? []);
        }
    }

    public static function getDependenciesConfig(): array
    {
        return [
            'repository' => [
                'class' => RepositoryInterface::class,
                'readable' => true,
            ],
        ];
    }

    public static function getDependenciesDefaultMap(): array
    {
        return [
            RepositoryInterface::class => Repository::class,
        ];
    }

    public function getService(): Service
    {
        return $this->owner;
    }

    public function getRepository(): ?RepositoryInterface
    {
        return $this->repository;
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * GETTERS FOR COMMON MODEL INFORMATION
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * @return array<string>
     */
    public function getModelNames(): array
    {
        return $this->context->getConductor()->getAllModelNames();
    }

    public function getModelClassesMap(): array
    {
        if (!$this->modelClassesMap) {
            $mapFile = $this->context->getConductor()->getModelClassesMapFile();
            $this->modelClassesMap = ($mapFile && $mapFile->exists())
                ? json_decode($mapFile->get(), true)
                : [];
        }

        return $this->modelClassesMap;
    }

    public function getModelClassName(string $modelName): ?string
    {
        $map = $this->getModelClassesMap();
        return $map[$modelName] ?? null;
    }

    public function getModelSchema(string $modelName): ?ModelSchema
    {
        /** @var Model $class */
        $class = $this->getModelClassName($modelName);
        if (!$class) {
            return null;
        }

        return $class::getModelSchema();
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * MANAGEMENT FOR MODEL CODE AND REPOSITORY STATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    public function compareModels(?array $modelNames = null): RefreshReport
    {
        $refresher = new ModelsRefresher($this->context);
        return $refresher->compare($modelNames);
    }

    public function refreshModels(?array $modelNames = null): RefreshReport
    {
        $refresher = new ModelsRefresher($this->context);
        return $refresher->run($modelNames);
    }

    public function compareRepository(?array $modelNames = null): ReportInterface
    {
        return $this->repository->checkModelsStatus($modelNames);
    }

    public function createNewMigration(): void
    {
        $this->repository->createNewMigration();
    }

    public function refreshMigrations(): ReportInterface
    {
        $changes = $this->compareRepository();
        return $this->repository->buildMigrations($changes);
    }

    public function runMigrations(?int $count = null): ReportInterface
    {
        return $this->repository->executeMigrations($count);
    }

    public function rollbackMigrations(?int $count = null): ReportInterface
    {
        return $this->repository->rollbackMigrations($count);
    }
}

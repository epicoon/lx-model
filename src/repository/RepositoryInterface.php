<?php

namespace lx\model\repository;

use lx\model\managerTools\ModelsContext;
use lx\model\Model;
use lx\model\repository\db\tools\RepositoryContext;

/**
 * Interface RepositoryInterface
 * @package lx\model
 */
interface RepositoryInterface
{
    public function setContext(ModelsContext $context);
    public function setConfig(array $config);
    public function getContext(): RepositoryContext; //TODO interface
    public function isSingle(): bool;
    public function checkModelsStatus(?array $modelNames = null): ReportInterface;
    public function createNewMigration(): void;
    public function buildMigrations(ReportInterface $changes): ReportInterface;
    public function executeMigrations(?int $count = null): ReportInterface;
    public function rollbackMigrations(?int $count = null): ReportInterface;
    public function hasUnappliedMigrations(): bool;
    /**
     * @return MigrationInterface[]
     */
    public function getMigrations(): array;
    public function getMigration(string $name): MigrationInterface;

    public function hold(): void;
    public function drop(): void;
    public function commit(): bool;

    public function getCount(string $modelName, ?array $condition = null): int;
    public function saveModel(Model $model): bool;
    public function deleteModel(Model $model): bool;
    /**
     * @param string $modelName
     * @param int|array $condition
     * @return Model|null
     */
    public function findModel(string $modelName, $condition): ?Model;
    public function findModelAsArray(string $modelName, int $id): ?array;
    public function findModels(string $modelName, ?array $condition = null): array;
    public function findRelatedModels(Model $model, string $relationName): array;
    /**
     * @param Model[] $models
     * @return bool
     */
    public function saveModels(iterable $models): bool;

    /**
     * @param Model[] $models
     * @return bool
     */
    public function deleteModels(iterable $models): bool;

    public function deleteModelsByCondition(string $modelName, ?array $condition = null): void;
}

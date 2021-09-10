<?php

namespace lx\model\repository;

use lx\model\managerTools\ModelsContext;
use lx\model\Model;
use lx\model\repository\db\tools\RepositoryContext;

interface RepositoryInterface
{
    public function setContext(ModelsContext $context): void;
    public function setConfig(array $config): void;
    public function getContext(): RepositoryContext; //TODO interface
    public function isSingle(): bool;
    public function checkModelsStatus(?array $modelNames = null): ReportInterface;
    public function createNewMigration(): void;
    public function buildMigrations(ReportInterface $changes): ReportInterface;
    public function executeMigrations(?int $count = null): ReportInterface;
    public function rollbackMigrations(?int $count = null): ReportInterface;
    public function hasUnappliedMigrations(): bool;
    /**
     * @return array<MigrationInterface>
     */
    public function getMigrations(): array;
    public function getMigration(string $name): MigrationInterface;

    public function isOnHold(): bool;
    public function hold(): void;
    public function drop(): void;
    public function commit(): bool;

    public function getCount(string $modelName, ?array $condition = null): int;
    public function saveModel(Model $model): bool;
    public function deleteModel(Model $model): bool;
    /**
     * @param int|array $condition
     */
    public function findModel(string $modelName, $condition, bool $useUnitMap = true): ?Model;
    /**
     * @return null|iterable<Model> $models
     */
    public function findModelAsArray(string $modelName, int $id, bool $useUnitMap = true): ?iterable;
    /**
     * @return iterable<Model> $models
     */
    public function findModels(string $modelName, ?array $condition = null, bool $useUnitMap = true): iterable;
    /**
     * @return iterable<Model> $models
     */
    public function findRelatedModels(Model $model, string $relationName): iterable;
    /**
     * @param iterable<Model> $models
     */
    public function saveModels(iterable $models): bool;

    /**
     * @param iterable<Model> $models
     */
    public function deleteModels(iterable $models): bool;

    public function deleteModelsByCondition(string $modelName, ?array $condition = null): void;
}

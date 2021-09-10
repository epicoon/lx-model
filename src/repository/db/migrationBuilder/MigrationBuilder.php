<?php

namespace lx\model\repository\db\migrationBuilder;

use lx\DataFileInterface;
use lx\model\repository\db\migrationExecutor\actions\MigrationActionTypeEnum;
use lx\model\repository\db\tools\RepositoryContext;
use lx\model\repository\db\tools\SyncSchema;
use lx\model\repository\ReportInterface;

class MigrationBuilder
{
    private RepositoryContext $context;
    private MigrationBuildReport $report;

    public function __construct(RepositoryContext $context)
    {
        $this->context = $context;
        $this->report = new MigrationBuildReport();
    }

    public function createEmpty(): void
    {
        $data = [
            'actions' => [
                [
                    'type' => 'TODO',
                ],
            ],
        ];

        $this->genMigration($data);
    }

    public function createByReport(ReportInterface $report): ReportInterface
    {
        $reportArray = $report->toArray();
        if (!empty($reportArray['unappliedMigrations'])) {
            return $report;
        }

        $data = [];
        $actions = $this->getCreateTableActions($reportArray);
        if (!empty($actions)) {
            $data['actions'] = $actions;
        }

        $modelChanges = $this->getChangeTableActions($reportArray);
        if (!empty($modelChanges)) {
            $data['modelChanges'] = $modelChanges;
        }

        if (!empty($data)) {
            $this->genMigration($data);
        }

        return $this->report;
    }

    private function getCreateTableActions(array $report): array
    {
        $modelsNeedTable = $report['modelsNeedTable'] ?? [];
        $result = [];
        foreach ($modelsNeedTable as $modelName) {
            $schema = $this->context->getModelManager()->getModelSchema($modelName);
            $fields = [];
            $fieldConverter = new SyncSchema($this->context, $modelName);

            foreach ($schema->getFields() as $fieldName => $field) {
                $fields[$fieldName] = $fieldConverter->fieldToBasicDefinitionArray($field);
            }

            $schemaArray = [
                'name' => $modelName,
            ];
            if (!empty($fields)) {
                $schemaArray['fields'] = $fields;
            }

            $result[] = [
                'type' => MigrationActionTypeEnum::CREATE_TABLE,
                'schema' => $schemaArray,
            ];
        }

        return $result;
    }

    private function getChangeTableActions(array $report): array
    {
        $modelsChanged = $report['modelsChanged'] ?? [];

        $result = [];
        foreach ($modelsChanged as $modelName => $allChanges) {
            $data = [
                'modelName' => $modelName,
                'actions' => [],
            ];

            $fieldChanges = $allChanges['fields'] ?? null;
            if ($fieldChanges) {
                $data['actions'] = array_merge(
                    $data['actions'],
                    $this->getFieldChangeActions($modelName, $fieldChanges)
                );
            }

            $relationChanges = $allChanges['relations'] ?? null;
            if ($relationChanges) {
                $data['actions'] = array_merge(
                    $data['actions'],
                    $this->getRelationChangeActions($modelName, $relationChanges)
                );
            }

            $result[] = $data;
        }

        return $result;
    }

    private function getFieldChangeActions(string $modelName, array $fieldChanges): array
    {
        $result = [];

        $syncSchema = new SyncSchema($this->context, $modelName);
        $oldSchema = $syncSchema->restoreModelSchema()->getModelSchema();
        $newSchema = $syncSchema->reset()->getModelSchema();

        foreach ($fieldChanges['changed'] as $fieldName) {
            $newField = $newSchema->getField($fieldName);
            $oldField = $oldSchema->getField($fieldName);
            $result[] = [
                'type' => MigrationActionTypeEnum::CHANGE_FIELD,
                'fieldName' => $fieldName,
                'oldDefinition' => $syncSchema->fieldToBasicDefinitionArray($oldField),
                'newDefinition' => $syncSchema->fieldToBasicDefinitionArray($newField),
            ];
        }

        foreach ($fieldChanges['renamed'] as $pare) {
            $result[] = [
                'type' => MigrationActionTypeEnum::RENAME_FIELD,
                'oldFieldName' => $pare['old'],
                'newFieldName' => $pare['new'],
            ];
        }

        foreach ($fieldChanges['added'] as $fieldName) {
            $field = $newSchema->getField($fieldName);
            $result[] = [
                'type' => MigrationActionTypeEnum::ADD_FIELD,
                'fieldName' => $fieldName,
                'definition' => $syncSchema->fieldToBasicDefinitionArray($field),
            ];
        }

        foreach ($fieldChanges['deleted'] as $fieldName) {
            $field = $oldSchema->getField($fieldName);
            $result[] = [
                'type' => MigrationActionTypeEnum::DEL_FIELD,
                'fieldName' => $fieldName,
                'definition' => $syncSchema->fieldToBasicDefinitionArray($field),
            ];
        }

        return $result;
    }

    private function getRelationChangeActions(string $modelName, array $relationChanges): array
    {
        $result = [];

        $syncSchema = new SyncSchema($this->context, $modelName);
        $oldSchema = $syncSchema->restoreModelSchema()->getModelSchema();
        $newSchema = $syncSchema->reset()->getModelSchema();

        foreach ($relationChanges['changed'] as $relationName) {
            $newRelation = $newSchema->getRelation($relationName);
            $oldRelation = $oldSchema->getRelation($relationName);
            $result[] = [
                'type' => MigrationActionTypeEnum::CHANGE_RELATION,
                'relationName' => $relationName,
                'oldDefinition' => $syncSchema->relationToBasicDefinitionArray($oldRelation),
                'newDefinition' => $syncSchema->relationToBasicDefinitionArray($newRelation),
            ];
        }

        foreach ($relationChanges['renamed'] as $pare) {
            $result[] = [
                'type' => MigrationActionTypeEnum::RENAME_RELATION,
                'oldFieldName' => $pare['old'],
                'newFieldName' => $pare['new'],
            ];
        }

        foreach ($relationChanges['added'] as $relationName) {
            $relation = $newSchema->getRelation($relationName);
            $result[] = [
                'type' => MigrationActionTypeEnum::ADD_RELATION,
                'relationName' => $relationName,
                'definition' => $syncSchema->relationToBasicDefinitionArray($relation),
            ];
        }

        foreach ($relationChanges['deleted'] as $relationName) {
            $relation = $oldSchema->getRelation($relationName);
            $result[] = [
                'type' => MigrationActionTypeEnum::DEL_RELATION,
                'relationName' => $relationName,
                'definition' => $syncSchema->relationToBasicDefinitionArray($relation),
            ];
        }

        return $result;
    }

    private function genMigration(array $data): void
    {
        $migrationName = date('Ymd_His') . '.' . $this->context->getMigrationExtension();
        $dir = $this->context->getConductor()->getMigrationsDirectory();
        $dir->make();
        $migrationFile = $dir->makeFile($migrationName, DataFileInterface::class);
        $migrationFile->put($data);

        $this->report->addToNewMigrations($migrationName);
    }
}

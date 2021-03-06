<?php

namespace lx\model\repository\db\comparator;

use lx\model\repository\db\Repository;
use lx\model\repository\db\tools\RepositoryContext;
use lx\model\repository\db\tools\SyncSchema;
use lx\model\schema\ModelSchema;

/**
 * Class ModelComparator
 * @package lx\model\repository\db\comparator
 */
class ModelComparator
{
    private RepositoryContext $context;
    private Repository $repository;
    private string $modelName;
    private CompareRepositoryReport $report;

    public function __construct(RepositoryContext $context, string $modelName)
    {
        $this->context = $context;
        $this->repository = $context->getRepository();
        $this->modelName = $modelName;
        $this->report = new CompareRepositoryReport();
    }

    public function run(): CompareRepositoryReport
    {
        $db = $this->repository->getMainDb();
        $tableName = $this->context->getNameConverter()->getTableName($this->modelName);
        if (!$db->tableExists($tableName)) {
            $this->report->addToModelsNeedTable($this->modelName);
            return $this->report;
        }

        $syncSchema = new SyncSchema($this->context, $this->modelName);
        $schemaByRepo = $syncSchema->restoreModelSchema()->getModelSchema();
        $schemaByCode = $syncSchema->reset()->getModelSchema();

        $diffs = $this->compareSchemas($schemaByCode, $schemaByRepo);
        if (!empty($diffs)) {
            $this->report->addToModelsChanged($this->modelName, $diffs);
        }

        return $this->report;
    }

    /**
     * @param ModelSchema $schemaByCode
     * @param ModelSchema $schemaByRepo
     * @return array
     *     [
     *         ?fields=>[changed[], renamed[], added[], deleted[]],
     *         ?relations=>[changed[], renamed[], added[], deleted[]]
     *     ]
     */
    private function compareSchemas(ModelSchema $schemaByCode, ModelSchema $schemaByRepo): array
    {
        $fieldDiffs = (new FieldsComparator())->run($schemaByCode, $schemaByRepo);
        $relationDiffs = (new RelationsComparator())->run($schemaByCode, $schemaByRepo);

        $diffs = [];
        if ($this->hasDiffs($fieldDiffs)) {
            $diffs['fields'] = $fieldDiffs;
        }

        if ($this->hasDiffs($relationDiffs)) {
            $diffs['relations'] = $relationDiffs;
        }

        return $diffs;
    }

    private function hasDiffs(array $diffs): bool
    {
        return !empty($diffs['changed'])
            || !empty($diffs['renamed'])
            || !empty($diffs['added'])
            || !empty($diffs['deleted']);
    }
}

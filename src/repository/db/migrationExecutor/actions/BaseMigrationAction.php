<?php

namespace lx\model\repository\db\migrationExecutor\actions;

use Exception;
use lx\model\repository\db\migrationExecutor\actions\content\AddModelsAction;
use lx\model\repository\db\migrationExecutor\actions\table\CreateTableAction;
use lx\model\repository\db\migrationExecutor\actions\table\DropTableAction;
use lx\model\repository\db\Repository;
use lx\model\repository\db\migrationExecutor\actions\field\AddFieldAction;
use lx\model\repository\db\migrationExecutor\actions\field\ChangeFieldAction;
use lx\model\repository\db\migrationExecutor\actions\field\DelFieldAction;
use lx\model\repository\db\migrationExecutor\actions\field\RenameFieldAction;
use lx\model\repository\db\migrationExecutor\actions\relation\AddRelationAction;
use lx\model\repository\db\migrationExecutor\actions\relation\ChangeRelationAction;
use lx\model\repository\db\migrationExecutor\actions\relation\DelRelationAction;
use lx\model\repository\db\migrationExecutor\actions\relation\RenameRelationAction;
use lx\model\repository\db\migrationExecutor\MigrationExecuteReport;
use lx\model\repository\db\tools\RepositoryContext;
use lx\model\repository\db\tools\Migration;

abstract class BaseMigrationAction
{
    protected RepositoryContext $context;
    protected Repository $repository;
    protected MigrationExecuteReport $report;
    protected Migration $migration;
    protected array $data;

    protected function __construct(RepositoryContext $context, array $data)
    {
        $this->context = $context;
        $this->repository = $context->getRepository();
        $this->report = new MigrationExecuteReport();

        $this->migration = $data['migration'];
        $this->data = $data;
    }

    public static function create(RepositoryContext $context, array $data): BaseMigrationAction
    {
        $type = $data['type'] ?? null;
        if (!$type) {
            throw new Exception('Migration action type is missed');
        }

        switch ($type) {
            case MigrationActionTypeEnum::CREATE_TABLE:
                return new CreateTableAction($context, $data);

            case MigrationActionTypeEnum::DROP_TABLE:
                return new DropTableAction($context, $data);

            case MigrationActionTypeEnum::CHANGE_FIELD:
                return new ChangeFieldAction($context, $data);

            case MigrationActionTypeEnum::RENAME_FIELD:
                return new RenameFieldAction($context, $data);

            case MigrationActionTypeEnum::ADD_FIELD:
                return new AddFieldAction($context, $data);

            case MigrationActionTypeEnum::DEL_FIELD:
                return new DelFieldAction($context, $data);

            case MigrationActionTypeEnum::CHANGE_RELATION:
                return new ChangeRelationAction($context, $data);

            case MigrationActionTypeEnum::RENAME_RELATION:
                return new RenameRelationAction($context, $data);

            case MigrationActionTypeEnum::ADD_RELATION:
                return new AddRelationAction($context, $data);

            case MigrationActionTypeEnum::DEL_RELATION:
                return new DelRelationAction($context, $data);

            case MigrationActionTypeEnum::ADD_MODELS:
                return new AddModelsAction($context, $data);

            default:
                throw new Exception("Migration action type '$type' is wrong");
        }
    }

    public function run(): MigrationExecuteReport
    {
        $this->execute();
        return $this->report;
    }

    abstract public function inverse(): BaseMigrationAction;

    abstract protected function execute(): void;

    protected function addError(string $error): void
    {
        $data = [
            'migration' => $this->data['migration']->getVersion(),
            'error' => $error,
        ];

        $db = $this->context->getRepository()->getMainDb();
        if ($db->hasError()) {
            $data['dbError'] = $db->getError();
        }

        $this->report->addToMigrationErrors($data);
    }
}

<?php

namespace lx\model\repository\db\migrationExecutor\actions\relation;

use lx\model\repository\db\migrationExecutor\actions\BaseMigrationAction;
use lx\model\schema\relation\RelationTypeEnum;

abstract class LifeCycleRelationAction extends BaseMigrationAction
{
    protected string $relationType;
    protected string $modelName;
    protected string $attributeName;
    protected string $relModelName;
    protected ?string $relAttributeName;

    abstract protected function executeToOne(): void;
    abstract protected function executeManyToMany(): void;

    protected function execute(): void
    {
        $this->relationType = $this->data['definition']['type'];
        $this->modelName = $this->data['modelName'];
        $this->attributeName = $this->data['relationName'];
        $this->relModelName = $this->data['definition']['relModel'];
        $this->relAttributeName = $this->data['definition']['relAttribute'] ?? null;

        $relationType = $this->data['definition']['type'];
        switch ($relationType) {
            case RelationTypeEnum::ONE_TO_ONE:
            case RelationTypeEnum::MANY_TO_ONE:
                $this->executeToOne();
                break;
            case RelationTypeEnum::MANY_TO_MANY:
                $this->executeManyToMany();
                break;
        }
    }
}

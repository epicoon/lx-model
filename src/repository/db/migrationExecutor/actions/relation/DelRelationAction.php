<?php

namespace lx\model\repository\db\migrationExecutor\actions\relation;

use lx\DbTableBuilder;
use lx\DbTableSchema;
use lx\model\repository\db\migrationExecutor\actions\BaseMigrationAction;
use lx\model\repository\db\migrationExecutor\actions\MigrationActionTypeEnum;

class DelRelationAction extends LifeCycleRelationAction
{
    public function inverse(): BaseMigrationAction
    {
        $data = $this->data;
        $data['type'] = MigrationActionTypeEnum::ADD_RELATION;
        return BaseMigrationAction::create($this->context, $data);
    }

    protected function executeToOne(): void
    {
        $nameConverter = $this->context->getNameConverter();
        $tableName = $nameConverter->getTableName($this->data['modelName']);
        $fieldName = 'fk_' . $nameConverter->getFieldName($this->modelName, $this->attributeName);

        $tableSchema = DbTableSchema::createByTableName(
            $this->context->getRepository()->getMainDb(),
            $tableName
        );
        $builder = new DbTableBuilder($tableSchema);
        $result = $builder->delField($fieldName);
        if (!$result) {
            $this->addError('Can not delete a field');
        }

    }

    protected function executeManyToMany(): void
    {
        $nameConverter = $this->context->getNameConverter();
        $tableName = $nameConverter->getManyToManyTableName(
            $this->modelName,
            $this->attributeName,
            $this->relModelName,
            $this->relAttributeName
        );
        $schema = DbTableSchema::createByTableName($this->context->getRepository()->getMainDb(), $tableName);
        $builder = new DbTableBuilder($schema);
        $builder->dropTable();
    }
}

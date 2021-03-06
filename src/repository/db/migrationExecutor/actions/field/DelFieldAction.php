<?php

namespace lx\model\repository\db\migrationExecutor\actions\field;

use lx\DbTableBuilder;
use lx\DbTableSchema;
use lx\model\repository\db\migrationExecutor\actions\BaseMigrationAction;
use lx\model\repository\db\migrationExecutor\actions\MigrationActionTypeEnum;
use lx\model\repository\db\migrationExecutor\CustomTypesRecorder;

/**
 * Class DelFieldAction
 * @package lx\model\repository\db\migrationExecutor\actions
 */
class DelFieldAction extends BaseMigrationAction
{
    public function inverse(): BaseMigrationAction
    {
        $data = $this->data;
        $data['type'] = MigrationActionTypeEnum::ADD_FIELD;
        return BaseMigrationAction::create($this->context, $data);
    }

    protected function execute(): void
    {
        $nameConverter = $this->context->getNameConverter();
        $tableName = $nameConverter->getTableName($this->data['modelName']);
        $fieldName = $nameConverter->getFieldName($this->data['modelName'], $this->data['fieldName']);

        $tableSchema = DbTableSchema::createByTableName(
            $this->context->getRepository()->getMainDb(),
            $tableName
        );
        $builder = new DbTableBuilder($tableSchema);
        $result = $builder->delField($fieldName);
        if (!$result) {
            $this->addError('Can not delete a field');
        }

        $customTypeRecorder = new CustomTypesRecorder($this->context);
        $customTypeRecorder->onDropColumn($tableName, $fieldName);
    }
}

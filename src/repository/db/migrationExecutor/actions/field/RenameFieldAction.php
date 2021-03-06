<?php

namespace lx\model\repository\db\migrationExecutor\actions\field;

use lx\DbTableBuilder;
use lx\DbTableSchema;
use lx\model\repository\db\migrationExecutor\actions\BaseMigrationAction;

/**
 * Class RenameFieldAction
 * @package lx\model\repository\db\migrationExecutor\actions
 */
class RenameFieldAction extends BaseMigrationAction
{
    public function inverse(): BaseMigrationAction
    {
        $old = $this->data['oldFieldName'];
        $this->data['oldFieldName'] = $this->data['newFieldName'];
        $this->data['newFieldName'] = $old;
        return $this;
    }

    protected function execute(): void
    {
        $nameConverter = $this->context->getNameConverter();
        $tableName = $nameConverter->getTableName($this->data['modelName']);
        $oldFieldName = $nameConverter->getFieldName($this->data['modelName'], $this->data['oldFieldName']);
        $newFieldName = $nameConverter->getFieldName($this->data['modelName'], $this->data['newFieldName']);

        $tableSchema = DbTableSchema::createByTableName(
            $this->context->getRepository()->getMainDb(),
            $tableName
        );
        $builder = new DbTableBuilder($tableSchema);
        $result = $builder->renameField($oldFieldName, $newFieldName);
        if (!$result) {
            $this->addError('Can not rename a field');
        }
    }
}

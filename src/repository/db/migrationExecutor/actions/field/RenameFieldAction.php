<?php

namespace lx\model\repository\db\migrationExecutor\actions\field;

use lx\DbTableEditor;
use lx\DbTableSchema;
use lx\model\repository\db\migrationExecutor\actions\BaseMigrationAction;

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

        $editor = new DbTableEditor();
        $editor->loadTableSchema($this->context->getRepository()->getMainDb(), $tableName);
        $result = $editor->renameField($oldFieldName, $newFieldName);
        if (!$result) {
            $this->addError('Can not rename a field');
        }
    }
}

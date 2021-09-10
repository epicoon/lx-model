<?php

namespace lx\model\repository\db\migrationExecutor\actions;

class MigrationActionTypeEnum
{
    const CREATE_TABLE = 'createTable';
    const DROP_TABLE = 'dropTable';

    const CHANGE_FIELD = 'changeField';
    const RENAME_FIELD = 'renameField';
    const ADD_FIELD = 'addField';
    const DEL_FIELD = 'delField';

    const CHANGE_RELATION = 'changeRelation';
    const RENAME_RELATION = 'renameRelation';
    const ADD_RELATION = 'addRelation';
    const DEL_RELATION = 'delRelation';

    const ADD_MODELS = 'addModels';
}

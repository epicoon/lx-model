<?php

namespace lx\model\cli;

use lx;
use lx\ServiceCliExecutor;

/**
 * Class MigrationsManage
 * @package lx\model\cli
 */
class MigrationsManage extends ServiceCliExecutor
{
    public function run()
    {
        $this->sendPlugin([
            'name' => 'lx/model:migrationManager',
            'header' => 'Migrations manager',
            'message' => 'Migrations manage plugin loaded',
        ]);
    }
}

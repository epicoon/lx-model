<?php

namespace lx\model\cli;

use lx;
use lx\ServiceCliExecutor;

class MigrationsManage extends ServiceCliExecutor
{
    public function run(): void
    {
        $this->sendPlugin([
            'name' => 'lx/model:migrationManager',
            'header' => 'Migrations manager',
            'message' => 'Migrations manage plugin loaded',
        ]);
    }
}

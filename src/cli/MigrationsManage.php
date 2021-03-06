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
        $plugin = lx::$app->getPlugin('lx/model:migrationManager');
        $pluginData = $plugin->run();

        $processor = $this->processor;
        $processor->setData([
            'code' => 'ext',
            'type' => 'plugin',
            'message' => 'Migrations manage plugin loaded',
            'header' => 'Migrations manager',
            'plugin' => $pluginData->getData(),
        ]);
        $processor->done();
    }
}

<?php

namespace lx\model\cli;

use lx\ModelManagerInterface;
use lx\ServiceCliExecutor;

/**
 * Class CreateMigration
 * @package lx\model\cli
 */
class CreateMigration extends ServiceCliExecutor
{
    public function run()
    {
        $this->defineService();
        if (!$this->service) {
            $this->processor->outln('Choose a service');
            return;
        }

        if (!$this->service->modelManager) {
            $this->processor->outln('The service does not have a model manager');
            return;
        }

        //TODO запрещать создавать новые миграции, если есть ненакаченные (вообще если не актуальное состояние моделей)

        /** @var ModelManagerInterface $modelManager */
        $modelManager = $this->service->modelManager;
        $modelManager->createNewMigration();

        $this->processor->outln('Done');
    }
}

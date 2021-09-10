<?php

namespace lx\model\cli;

use lx\ServiceCliExecutor;

class CreateMigration extends ServiceCliExecutor
{
    public function run(): void
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

        $modelManager = $this->service->modelManager;
        $modelManager->createNewMigration();

        $this->processor->outln('Done');
    }
}

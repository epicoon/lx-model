<?php

namespace lx\model\repository\db;

use lx\model\managerTools\ModelsContext;

class SingleRepository extends Repository
{
    public function setContext(ModelsContext $context): void
    {
        parent::setContext($context);

        $connector = $this->getConnector();
        if ($connector->hasConnection($this->getConfig(self::CONNECTION_KEY_MAIN))) {
            throw new \Exception('Single repository double initialisation has been attempted');
        }
    }

    public function isSingle(): bool
    {
        return true;
    }
}

<?php

namespace lx\model\repository\db;

use lx\model\managerTools\ModelsContext;

/**
 * Class SingleRepository
 * @package lx\model\repository\db
 */
class SingleRepository extends Repository
{
    private static array $registered = [];

    public function setContext(ModelsContext $context)
    {
        parent::setContext($context);

        $connector = $this->getConnector();
        $key = $connector->getConnectionKey($this->getConfig(self::CONNECTION_KEY_MAIN));
        if (in_array($key, self::$registered)) {
            throw new \Exception('Single repository double initialisation has been attempted');
        }

        self::$registered[] = $key;
    }

    public function isSingle(): bool
    {
        return true;
    }
}
<?php

namespace lx\model\repository\db\tools;

use lx\model\repository\db\Repository;
use lx\model\managerTools\ModelsContext;

/**
 * Class RepositoryContext
 * @package lx\model\repository\db
 */
class RepositoryContext extends ModelsContext
{
    private Repository $repository;
    private NameConverter $nameConverter;

    public function __construct(ModelsContext $context, Repository $repository)
    {
        parent::__construct(
            $context->getService(),
            $context->getModelManager(),
            $context->getModelSchemasPath(),
            $context->getModelsPath(),
            $context->getModelSchemasExtension(),
            $context->getMigrationExtension()
        );

        $this->repository = $repository;
        $this->nameConverter = new NameConverter($this);
    }

    public function getRepository(): Repository
    {
        return $this->repository;
    }

    public function getNameConverter(): NameConverter
    {
        return $this->nameConverter;
    }
}

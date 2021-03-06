<?php

namespace lx\model\schema\field\definition;

/**
 * Class AbstractDefinition
 * @package lx\model\schema\field\definition
 */
abstract class AbstractDefinition
{
    abstract public function init(array $definition);

    abstract public function toArray(): array;

    public function isEqual(AbstractDefinition $fieldDefinition): bool
    {
        return true;
    }
}

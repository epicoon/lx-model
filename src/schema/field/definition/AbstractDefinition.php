<?php

namespace lx\model\schema\field\definition;

abstract class AbstractDefinition
{
    abstract public function init(array $definition): void;

    abstract public function toArray(): array;
    
    abstract public function toString(): string;

    public function isEqual(AbstractDefinition $fieldDefinition): bool
    {
        return true;
    }
}

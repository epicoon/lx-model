<?php

namespace lx\model\schema\field\definition;

use lx\model\schema\field\type\TypeString;

class StringDefinition extends AbstractDefinition
{
    const DEFAULT_LENGTH = 256;

    private int $size;

    public function init(array $definition): void
    {
        $this->size = (int)($definition['size'] ?? self::DEFAULT_LENGTH);
    }

    public function isEqual(AbstractDefinition $fieldDefinition): bool
    {
        if (!($fieldDefinition instanceof StringDefinition)) {
            return false;
        }

        return $this->size == $fieldDefinition->size;
    }

    public function toArray(): array
    {
        return [
            'size' => $this->size,
        ];
    }
    
    public function toString(): string
    {
        return TypeString::TYPE . "({$this->size})";
    }
}

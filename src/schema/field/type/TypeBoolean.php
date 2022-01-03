<?php

namespace lx\model\schema\field\type;

use lx\model\schema\field\definition\AbstractDefinition;

class TypeBoolean extends Type
{
    const TYPE = 'bool';

    public function isCustom(): bool
    {
        return false;
    }

    /**
     * @param mixed $value
     */
    public function validateValue(AbstractDefinition $definition, $value): bool
    {
        return ($value === true || $value === false);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function normalizeValue(AbstractDefinition $definition, $value)
    {
        return (bool)$value;
    }

    /**
     * @return bool
     */
    public function getValueIfRequired(AbstractDefinition $definition)
    {
        return false;
    }
}

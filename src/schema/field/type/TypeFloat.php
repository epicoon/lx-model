<?php

namespace lx\model\schema\field\type;

use lx\model\schema\field\definition\AbstractDefinition;

class TypeFloat extends Type
{
    const TYPE = 'float';

    public function isCustom(): bool
    {
        return false;
    }

    /**
     * @param mixed $value
     */
    public function validateValue(AbstractDefinition $definition, $value): bool
    {
        return (filter_var($value, FILTER_VALIDATE_FLOAT) !== false);
    }

    /**
     * @param mixed $value
     * @return float
     */
    public function normalizeValue(AbstractDefinition $definition, $value)
    {
        return (float)$value;
    }

    /**
     * @return float
     */
    public function getValueIfRequired(AbstractDefinition $definition)
    {
        return 0.0;
    }
}

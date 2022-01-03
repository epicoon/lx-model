<?php

namespace lx\model\schema\field\type;

use lx\model\schema\field\definition\AbstractDefinition;

class TypeInteger extends Type
{
    const TYPE = 'int';

    public function isCustom(): bool
    {
        return false;
    }

    /**
     * @param mixed $value
     */
    public function validateValue(AbstractDefinition $definition, $value): bool
    {
        return (filter_var($value, FILTER_VALIDATE_INT) !== false);
    }

    /**
     * @param mixed $value
     * @return int
     */
    public function normalizeValue(AbstractDefinition $definition, $value)
    {
        return (int)$value;
    }

    /**
     * @return int
     */
    public function getValueIfRequired(AbstractDefinition $definition)
    {
        return 0;
    }
}

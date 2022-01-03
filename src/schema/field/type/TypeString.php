<?php

namespace lx\model\schema\field\type;

use lx\model\schema\field\definition\AbstractDefinition;
use lx\model\schema\field\definition\StringDefinition;
use lx\model\schema\field\parser\StringParser;

class TypeString extends Type
{
    const TYPE = 'string';

    public function isCustom(): bool
    {
        return false;
    }

    /**
     * @param mixed $value
     */
    public function validateValue(AbstractDefinition $definition, $value): bool
    {
        return (is_numeric($value) || is_string($value));
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function normalizeValue(AbstractDefinition $definition, $value)
    {
        if (is_numeric($value)) $value = ''.$value;
        return $value;
    }

    /**
     * @return string
     */
    public function getValueIfRequired(AbstractDefinition $definition)
    {
        return '';
    }
}

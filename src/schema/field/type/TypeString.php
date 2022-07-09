<?php

namespace lx\model\schema\field\type;

use lx\model\schema\field\definition\AbstractDefinition;
use lx\model\schema\field\parser\StringParser;
use lx\model\schema\field\RawValue;

class TypeString extends Type
{
    const TYPE = 'string';

    public function isCustom(): bool
    {
        return false;
    }

    public function validateValue(RawValue $value): bool
    {
        $val = $value->getValue();
        return (is_numeric($val) || is_string($val));
    }

    /**
     * @return string
     */
    public function normalizeValue(RawValue $value)
    {
        $val = $value->getValue();
        if (is_numeric($val)) $val = '' . $val;
        return $val;
    }

    /**
     * @return string
     */
    public function getValueIfRequired(AbstractDefinition $definition)
    {
        return '';
    }
}

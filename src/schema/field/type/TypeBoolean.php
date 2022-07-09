<?php

namespace lx\model\schema\field\type;

use lx\model\schema\field\definition\AbstractDefinition;
use lx\model\schema\field\RawValue;

class TypeBoolean extends Type
{
    const TYPE = 'bool';

    public function isCustom(): bool
    {
        return false;
    }

    public function validateValue(RawValue $value): bool
    {
        $val = $value->getValue();
        return ($val === true || $val === false);
    }

    /**
     * @return bool
     */
    public function normalizeValue(RawValue $value)
    {
        return (bool)($value->getValue());
    }

    /**
     * @return bool
     */
    public function getValueIfRequired(AbstractDefinition $definition)
    {
        return false;
    }
}

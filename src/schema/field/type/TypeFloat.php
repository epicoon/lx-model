<?php

namespace lx\model\schema\field\type;

use lx\model\schema\field\definition\AbstractDefinition;
use lx\model\schema\field\RawValue;

class TypeFloat extends Type
{
    const TYPE = 'float';

    public function isCustom(): bool
    {
        return false;
    }

    public function validateValue(RawValue $value): bool
    {
        return (filter_var($value->getValue(), FILTER_VALIDATE_FLOAT) !== false);
    }

    /**
     * @return float
     */
    public function normalizeValue(RawValue $value)
    {
        return (float)($value->getValue());
    }

    /**
     * @return float
     */
    public function getValueIfRequired(AbstractDefinition $definition)
    {
        return 0.0;
    }
}

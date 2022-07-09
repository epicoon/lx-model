<?php

namespace lx\model\schema\field\type;

use lx\model\schema\field\definition\AbstractDefinition;
use lx\model\schema\field\RawValue;

class TypeInteger extends Type
{
    const TYPE = 'int';

    public function isCustom(): bool
    {
        return false;
    }

    public function validateValue(RawValue $value): bool
    {
        return (filter_var($value->getValue(), FILTER_VALIDATE_INT) !== false);
    }

    /**
     * @return int
     */
    public function normalizeValue(RawValue $value)
    {
        return (int)($value->getValue());
    }

    /**
     * @return int
     */
    public function getValueIfRequired(AbstractDefinition $definition)
    {
        return 0;
    }
}

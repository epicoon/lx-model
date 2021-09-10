<?php

namespace lx\model\schema\field\type;

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
    public function validateValue($value): bool
    {
        return ($value === true || $value === false);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function normalizeValue($value)
    {
        return (bool)$value;
    }

    /**
     * @return bool
     */
    public function getValueIfRequired()
    {
        return false;
    }
}

<?php

namespace lx\model\schema\field\type;

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
    public function validateValue($value): bool
    {
        return (filter_var($value, FILTER_VALIDATE_INT) !== false);
    }

    /**
     * @param mixed $value
     * @return int
     */
    public function normalizeValue($value)
    {
        return (int)$value;
    }

    /**
     * @return int
     */
    public function getValueIfRequired()
    {
        return 0;
    }
}

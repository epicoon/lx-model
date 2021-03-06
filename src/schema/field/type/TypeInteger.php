<?php

namespace lx\model\schema\field\type;

/**
 * Class TypeInteger
 * @package lx\model\schema\field\type
 */
class TypeInteger extends Type
{
    public function getTypeName(): string
    {
        return PhpTypeEnum::INTEGER;
    }

    public function isCustom(): bool
    {
        return false;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function validateValue($value): bool
    {
        return (filter_var($value, FILTER_VALIDATE_INT) !== false);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function normalizeValue($value)
    {
        return (int)$value;
    }
}

<?php

namespace lx\model\schema\field\type;

/**
 * Class TypeBoolean
 * @package lx\model\schema\field\type
 */
class TypeBoolean extends Type
{
    public function getTypeName(): string
    {
        return PhpTypeEnum::BOOLEAN;
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
        return (filter_var($value, FILTER_VALIDATE_BOOLEAN) !== false);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function normalizeValue($value)
    {
        return (bool)$value;
    }
}

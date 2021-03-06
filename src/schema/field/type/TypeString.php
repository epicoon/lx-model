<?php

namespace lx\model\schema\field\type;

use lx\model\schema\field\definition\StringDefinition;
use lx\model\schema\field\parser\StringParser;

/**
 * Class TypeString
 * @package lx\model\schema\field\type
 */
class TypeString extends Type
{
    public function getTypeName(): string
    {
        return PhpTypeEnum::STRING;
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
        return (is_numeric($value) || is_string($value));
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function normalizeValue($value)
    {
        if (is_numeric($value)) $value = ''.$value;
        return $value;
    }

    public function getDefinitionClass(): string
    {
        return StringDefinition::class;
    }

    public function getParserClass(): string
    {
        return StringParser::class;
    }
}

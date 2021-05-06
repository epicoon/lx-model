<?php

namespace lx\model\schema\field\type;

use lx\model\schema\field\definition\AbstractDefinition;
use lx\model\schema\field\ModelField;
use lx\model\schema\field\definition\CommonDefinition;
use lx\model\schema\field\parser\CommonParser;
use lx\model\schema\ModelAttributeMethod;

abstract class Type
{
    abstract public function getTypeName(): string;

    /**
     * @param mixed $value
     */
    abstract public function validateValue($value): bool;

    /**
     * @param mixed $value
     * @return mixed
     */
    abstract public function normalizeValue($value);

    public function getPhpType(): string
    {
        return $this->getTypeName();
    }

    public function isCustom(): bool
    {
        return true;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function valueToRepository($value)
    {
        return $value;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function valueFromRepository($value)
    {
        return $value;
    }

    public function getMethodNames(ModelField $field): array
    {
        return [];
    }

    public function getMethodDefinition(string $methodName): ?ModelAttributeMethod
    {
        return null;
    }

    /**
     * @param mixed $currentValue
     * @return mixed
     */
    public function processMethod(ModelField $field, string $methodName, $currentValue, array $arguments)
    {
        return $currentValue;
    }

    public function getNewDefinition(): AbstractDefinition
    {
        $class = $this->getDefinitionClass();
        return new $class();
    }

    public function getDefinitionClass(): string
    {
        return CommonDefinition::class;
    }

    public function getParserClass(): string
    {
        return CommonParser::class;
    }
}

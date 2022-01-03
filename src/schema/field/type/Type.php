<?php

namespace lx\model\schema\field\type;

use lx\model\schema\field\definition\AbstractDefinition;
use lx\model\schema\field\definition\DefinitionFactory;
use lx\model\schema\field\ModelField;
use lx\model\schema\field\definition\CommonDefinition;
use lx\model\schema\field\parser\CommonParser;
use lx\model\schema\field\parser\ParserFactory;
use lx\model\schema\ModelAttributeMethod;

abstract class Type
{
    const TYPE = 'undefined';

    public function getTypeName(): string
    {
        return static::TYPE;
    }

    /**
     * @param mixed $value
     */
    abstract public function validateValue(AbstractDefinition $definition, $value): bool;

    /**
     * @param mixed $value
     * @return mixed
     */
    abstract public function normalizeValue(AbstractDefinition $definition, $value);

    /**
     * @return mixed
     */
    abstract public function getValueIfRequired(AbstractDefinition $definition);
    
    /**
     * @return mixed
     */
    public function getPrearrangedValue(AbstractDefinition $definition)
    {
        return null;
    }

    public function getPhpType(): string
    {
        return $this->getTypeName();
    }

    public function valuesAreEqual($value1, $value2): bool
    {
        return $value1 === $value2;
    }

    public function isCustom(): bool
    {
        return true;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function valueToRepository(AbstractDefinition $definition, $value)
    {
        return $value;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function valueFromRepository(AbstractDefinition $definition, $value)
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

    public function getDefinition(): AbstractDefinition
    {
        return DefinitionFactory::create($this->getTypeName());
    }
    
    public function getParser(): CommonParser
    {
        return ParserFactory::create($this->getTypeName());
    }
}

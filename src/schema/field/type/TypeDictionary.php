<?php

namespace lx\model\schema\field\type;

use lx\model\schema\field\definition\AbstractDefinition;
use lx\model\schema\field\RawValue;
use lx\model\schema\ModelAttributeMethod;
use lx\model\schema\field\ModelField;

class TypeDictionary extends Type
{
    const TYPE = 'dict';
    
    const METHOD_PREFIX_ADD = 'addTo';
    const METHOD_PREFIX_REMOVE = 'removeFrom';

    public function getPhpType(): string
    {
        return PhpTypeEnum::ARRAY;
    }

    public function validateValue(RawValue $value): bool
    {
        return is_array($value->getValue());
    }

    /**
     * @return array
     */
    public function normalizeValue(RawValue $value)
    {
        return (array)($value->getValue());
    }

    /**
     * @return array
     */
    public function getValueIfRequired(AbstractDefinition $definition)
    {
        return [];    
    }
    
    /**
     * @return string
     */
    public function valueToRepository(RawValue $value)
    {
        /** @var array $val */
        $val = $value->getValue();
        return json_encode($val);
    }

    /**
     * @return array
     */
    public function valueFromRepository(RawValue $value)
    {
        return json_decode($value->getValue(), true);
    }

    /**
     * @param array $value1
     * @param array $value2
     */
    public function valuesAreEqual($value1, $value2): bool
    {
        if (!is_array($value1) || !is_array($value2)) {
            return false;
        }

        return $value1 == $value2;
    }

    public function getMethodNames(ModelField $field): array
    {
        return [
            self::METHOD_PREFIX_ADD . ucfirst($field->getName()),
            self::METHOD_PREFIX_REMOVE . ucfirst($field->getName()),
        ];
    }

    public function getMethodDefinition(string $methodName): ?ModelAttributeMethod
    {
        if (strpos($methodName, self::METHOD_PREFIX_ADD) === 0) {
            return new ModelAttributeMethod([
                'key' => 'string',
                'value' => 'mixed',
                '@return' => 'void',
            ]);
        }

        if (strpos($methodName, self::METHOD_PREFIX_REMOVE) === 0) {
            return new ModelAttributeMethod([
                'key' => 'string',
                '@return' => 'void',
            ]);
        }

        return null;
    }

    /**
     * @param array $currentValue
     * @return array
     */
    public function processMethod(ModelField $field, string $methodName, $currentValue, array $arguments)
    {
        if (strpos($methodName, self::METHOD_PREFIX_ADD) === 0) {
            return $this->processAdd($currentValue, $arguments);
        }

        if (strpos($methodName, self::METHOD_PREFIX_REMOVE) === 0) {
            return $this->processRemove($currentValue, $arguments);
        }

        //TODO log
        return $currentValue;
    }

    private function processAdd(?array $currentValue, array $arguments): array
    {
        $result = $currentValue ?? [];
        $result[$arguments[0]] = $arguments[1] ?? null;
        return $result;
    }

    private function processRemove(?array $currentValue, array $arguments): array
    {
        $result = $currentValue ?? [];
        unset($result[$arguments[0]]);
        return $result;
    }
}

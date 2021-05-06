<?php

namespace lx\model\schema\field\type;

use lx\model\schema\ModelAttributeMethod;
use lx\model\schema\field\ModelField;

class TypeDictionary extends Type
{
    const METHOD_PREFIX_ADD = 'addTo';
    const METHOD_PREFIX_REMOVE = 'removeFrom';

    public function getTypeName(): string
    {
        return 'dict';
    }

    /**
     * @param mixed $value
     */
    public function validateValue($value): bool
    {
        return is_array($value);
    }

    /**
     * @param mixed $value
     * @return array
     */
    public function normalizeValue($value)
    {
        return (array)$value;
    }

    public function getPhpType(): string
    {
        return PhpTypeEnum::ARRAY;
    }

    /**
     * @param array $value
     * @return string
     */
    public function valueToRepository($value)
    {
        return json_encode($value);
    }

    /**
     * @param string $value
     * @return array
     */
    public function valueFromRepository($value)
    {
        return json_decode($value, true);
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

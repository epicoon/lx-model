<?php

namespace lx\model\schema\field\type;

use lx\model\schema\field\definition\AbstractDefinition;
use lx\model\schema\field\value\DecimalValue;

class TypeDecimal extends Type
{
    const TYPE = 'decimal';

    public function getPhpType(): string
    {
        return DecimalValue::class;
    }

    public function isCustom(): bool
    {
        return false;
    }

    /**
     * @return DecimalValue
     */
    public function getValueIfRequired(AbstractDefinition $definition)
    {
        $value = new DecimalValue($definition);
        $value->setIfRequired();
        return $value;
    }

    /**
     * @return DecimalValue
     */
    public function getPrearrangedValue(AbstractDefinition $definition)
    {
        return new DecimalValue($definition);
    }

    /**
     * @param DecimalValue|string $value
     */
    public function validateValue(AbstractDefinition $definition, $value): bool
    {
        if ($value instanceof DecimalValue) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        $arr = explode('.', $value);
        if (count($arr) !== 2) {
            return false;
        }

        $scale = $definition->toArray()['scale'];
        if (strlen($arr[1]) !== $scale) {
            return false;
        }

        return true;
    }

    /**
     * @param DecimalValue|string $value
     * @return DecimalValue
     */
    public function normalizeValue(AbstractDefinition $definition, $value)
    {
        if (is_string($value)) {
            return new DecimalValue($definition, $value);
        }

        return $value;
    }

    /**
     * @param DecimalValue|string $value1
     * @param DecimalValue|string $value2
     */
    public function valuesAreEqual($value1, $value2): bool
    {
        $str1 = ($value1 instanceof DecimalValue)
            ? $value1->toString()
            : $value1;
        if (!is_string($str1)) {
            return false;
        }

        $str2 = ($value2 instanceof DecimalValue)
            ? $value2->toString()
            : $value2;
        if (!is_string($str2)) {
            return false;
        }
        
        return $str1 == $str2;
    }

    /**
     * @param DecimalValue $value
     * @return string
     */
    public function valueToRepository(AbstractDefinition $definition, $value)
    {
        return $value->toString();
    }

    /**
     * @param string $value
     * @return DecimalValue
     */
    public function valueFromRepository(AbstractDefinition $definition, $value)
    {
        return new DecimalValue($definition, $value);
    }
}

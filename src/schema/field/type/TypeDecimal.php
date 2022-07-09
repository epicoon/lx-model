<?php

namespace lx\model\schema\field\type;

use lx\model\schema\field\definition\AbstractDefinition;
use lx\model\schema\field\RawValue;
use lx\model\schema\field\type\traits\PrepareValuesByPhpTypeAsClass;
use lx\model\schema\field\value\DecimalValue;

/**
 * @method DecimalValue getValueIfRequired(AbstractDefinition $definition)
 * @method DecimalValue getPrearrangedValue(AbstractDefinition $definition)
 */
class TypeDecimal extends Type
{
    use PrepareValuesByPhpTypeAsClass;
    
    const TYPE = 'decimal';

    public function getPhpType(): string
    {
        return DecimalValue::class;
    }

    public function isCustom(): bool
    {
        return false;
    }
    
    public function preprocessDefault($value)
    {
        if ($value === 0) {
            return '0';
        }
        
        return parent::preprocessDefault($value);
    }

    public function validateValue(RawValue $value): bool
    {
        $val = $value->getValue();
        if ($val instanceof DecimalValue) {
            return true;
        }

        if (!is_string($val)) {
            return false;
        }

        $arr = explode('.', $val);
        if (count($arr) !== 2) {
            return false;
        }

        $definition = $value->getDefinition();
        $scale = $definition->toArray()['scale'];
        if (strlen($arr[1]) !== $scale) {
            return false;
        }

        return true;
    }

    /**
     * @return DecimalValue
     */
    public function normalizeValue(RawValue $value)
    {
        $val = $value->getValue();
        
        if ($val instanceof DecimalValue) {
            return $val;
        }
        
        if (is_string($val)) {
            return DecimalValue::createFromRaw($value);
        }

        return $this->getPrearrangedValue($value->getDefinition());
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
     * @return string
     */
    public function valueToRepository(RawValue $value)
    {
        /** @var DecimalValue $val */
        $val = $value->getValue();
        return $val->toString();
    }

    /**
     * @return DecimalValue
     */
    public function valueFromRepository(RawValue $value)
    {
        return DecimalValue::createFromRaw($value);
    }
}

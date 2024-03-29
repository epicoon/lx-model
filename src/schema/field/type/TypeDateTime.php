<?php

namespace lx\model\schema\field\type;

use lx\model\schema\field\definition\AbstractDefinition;
use lx\model\schema\field\RawValue;
use lx\model\schema\field\type\traits\PrepareValuesByPhpTypeAsClass;
use lx\model\schema\field\value\DateTimeValue;

/**
 * @method DateTimeValue getValueIfRequired(AbstractDefinition $definition)
 * @method DateTimeValue getPrearrangedValue(AbstractDefinition $definition)
 */
class TypeDateTime extends Type
{
    use PrepareValuesByPhpTypeAsClass;

    const TYPE = 'datetime';

    public function getPhpType(): string
    {
        return DateTimeValue::class;
    }

    public function isCustom(): bool
    {
        return false;
    }

    public function validateValue(RawValue $value): bool
    {
        $val = $value->getValue();
        if (is_string($val)) {
            try {
                new \DateTime($val);
                return true;
            } catch (\Exception $exception) {
                return false;
            }
        }
        
        return $val instanceof \DateTime || $val instanceof DateTimeValue;
    }

    /**
     * @return DateTimeValue
     */
    public function normalizeValue(RawValue $value)
    {
        $val = $value->getValue();

        if ($val instanceof DateTimeValue) {
            return $val;
        }

        if (is_string($val) || $val instanceof \DateTime) {
            return DateTimeValue::createFromRaw($value);
        }

        return $this->getPrearrangedValue($value->getDefinition());
    }

    /**
     * @param \DateTime|DateTimeValue|string $value1
     * @param \DateTime|DateTimeValue|string $value2
     */
    public function valuesAreEqual($value1, $value2): bool
    {
        if (is_string($value1)) {
            try {
                $value1 = new \DateTime($value1);
            } catch (\Exception $exception) {
                return false;
            }
        }
        if (is_string($value2)) {
            try {
                $value2 = new \DateTime($value2);
            } catch (\Exception $exception) {
                return false;
            }
        }

        if (!($value1 instanceof \DateTime) && !($value1 instanceof DateTimeValue)) {
            return false;
        }
        if (!($value2 instanceof \DateTime) && !($value2 instanceof DateTimeValue)) {
            return false;
        }

        return $value1->format('Y-m-d h:i:s') === $value2->format('Y-m-d h:i:s');
    }

    /**
     * @return string
     */
    public function valueToRepository(RawValue $value)
    {
        /** @var DateTimeValue $val */
        $val = $value->getValue();
        //TODO кастомизировать форматирование
        return $val->format('Y-m-d h:i:s');
    }

    /**
     * @return DateTimeValue
     */
    public function valueFromRepository(RawValue $value)
    {
        return DateTimeValue::createFromRaw($value);
    }
}

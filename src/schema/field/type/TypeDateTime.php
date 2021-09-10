<?php

namespace lx\model\schema\field\type;

use lx\model\schema\field\value\DateTimeValue;

class TypeDateTime extends Type
{
    const TYPE = 'datetime';

    public function getPhpType(): string
    {
        return DateTimeValue::class;
    }

    public function isCustom(): bool
    {
        return false;
    }

    /**
     * @return DateTimeValue
     */
    public function getValueIfRequired()
    {
        $value = new DateTimeValue();
        $value->setIfRequired();
        return $value;
    }

    /**
     * @return DateTimeValue
     */
    public function getPrearrangedValue()
    {
        return new DateTimeValue();
    }

    /**
     * @param \DateTime|DateTimeValue|string $value
     */
    public function validateValue($value): bool
    {
        if (is_string($value)) {
            try {
                $date = new \DateTime($value);
                return true;
            } catch (\Exception $exception) {
                return false;
            }
        }
        
        return $value instanceof \DateTime || $value instanceof DateTimeValue;
    }

    /**
     * @param \DateTime|DateTimeValue|string $value
     * @return DateTimeValue
     */
    public function normalizeValue($value)
    {
        if (is_string($value) || $value instanceof \DateTime) {
            return new DateTimeValue($value);
        }
        return $value;
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
     * @param DateTimeValue $value
     * @return string
     */
    public function valueToRepository($value)
    {
        //TODO кастомизировать форматирование
        return $value->format('Y-m-d h:i:s');
    }

    /**
     * @param string $value
     * @return DateTimeValue
     */
    public function valueFromRepository($value)
    {
        return new DateTimeValue($value);
    }
}

<?php

namespace lx\model\schema\field\type;

use lx\model\schema\field\RawValue;
use lx\model\schema\field\type\traits\PrepareValuesByPhpTypeAsClass;
use lx\model\schema\field\value\TimeValue;

/**
 * @method TimeValue getValueIfRequired(AbstractDefinition $definition)
 * @method TimeValue getPrearrangedValue(AbstractDefinition $definition)
 */
class TypeTime extends Type
{
    use PrepareValuesByPhpTypeAsClass;

    const TYPE = 'time';

    public function getPhpType(): string
    {
        return TimeValue::class;
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

        return $val instanceof \DateTime || $val instanceof TimeValue;
    }

    /**
     * @return TimeValue
     */
    public function normalizeValue(RawValue $value)
    {
        $val = $value->getValue();

        if ($val instanceof TimeValue) {
            return $val;
        }

        if (is_string($val) || $val instanceof \DateTime) {
            return TimeValue::createFromRaw($value);
        }

        return $this->getPrearrangedValue($value->getDefinition());
    }

    /**
     * @param \DateTime|TimeValue|string $value1
     * @param \DateTime|TimeValue|string $value2
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

        if (!($value1 instanceof \DateTime) && !($value1 instanceof TimeValue)) {
            return false;
        }
        if (!($value2 instanceof \DateTime) && !($value2 instanceof TimeValue)) {
            return false;
        }

        return $value1->format('h:i:s') === $value2->format('h:i:s');
    }

    /**
     * @return string
     */
    public function valueToRepository(RawValue $value)
    {
        /** @var DateTimeValue $val */
        $val = $value->getValue();
        //TODO кастомизировать форматирование
        return $val->format('h:i:s');
    }

    /**
     * @return TimeValue
     */
    public function valueFromRepository(RawValue $value)
    {
        return TimeValue::createFromRaw($value);
    }
}

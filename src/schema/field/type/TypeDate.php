<?php

namespace lx\model\schema\field\type;

use lx\model\schema\field\RawValue;
use lx\model\schema\field\type\traits\PrepareValuesByPhpTypeAsClass;
use lx\model\schema\field\value\DateValue;

/**
 * @method DateValue getValueIfRequired(AbstractDefinition $definition)
 * @method DateValue getPrearrangedValue(AbstractDefinition $definition)
 */
class TypeDate extends Type
{
    use PrepareValuesByPhpTypeAsClass;

    const TYPE = 'date';

    public function getPhpType(): string
    {
        return DateValue::class;
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

        return $val instanceof \DateTime || $val instanceof DateValue;
    }

    /**
     * @return DateValue
     */
    public function normalizeValue(RawValue $value)
    {
        $val = $value->getValue();

        if ($val instanceof DateValue) {
            return $val;
        }

        if (is_string($val) || $val instanceof \DateTime) {
            return DateValue::createFromRaw($value);
        }

        return $this->getPrearrangedValue($value->getDefinition());
    }

    /**
     * @param \DateTime|DateValue|string $value1
     * @param \DateTime|DateValue|string $value2
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

        if (!($value1 instanceof \DateTime) && !($value1 instanceof DateValue)) {
            return false;
        }
        if (!($value2 instanceof \DateTime) && !($value2 instanceof DateValue)) {
            return false;
        }

        return $value1->format('Y-m-d') === $value2->format('Y-m-d');
    }

    /**
     * @return string
     */
    public function valueToRepository(RawValue $value)
    {
        /** @var DateValue $val */
        $val = $value->getValue();
        //TODO кастомизировать форматирование
        return $val->format('Y-m-d');
    }

    /**
     * @return DateValue
     */
    public function valueFromRepository(RawValue $value)
    {
        return DateValue::createFromRaw($value);
    }
}

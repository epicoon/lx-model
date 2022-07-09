<?php

namespace lx\model\schema\field\type;

use lx\model\schema\field\definition\AbstractDefinition;
use lx\model\schema\field\RawValue;
use lx\model\schema\field\type\traits\PrepareValuesByPhpTypeAsClass;
use lx\model\schema\field\value\DateIntervalValue;

/**
 * @method DateIntervalValue getValueIfRequired(AbstractDefinition $definition)
 * @method DateIntervalValue getPrearrangedValue(AbstractDefinition $definition)
 */
class TypeDateInterval extends Type
{
    use PrepareValuesByPhpTypeAsClass;

    const TYPE = 'interval';

    public function getPhpType(): string
    {
        return DateIntervalValue::class;
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
                new \DateInterval($val);
                return true;
            } catch (\Exception $exception) {
                return false;
            }
        }

        return $val instanceof \DateInterval || $val instanceof DateIntervalValue;
    }

    /**
     * @return DateIntervalValue
     */
    public function normalizeValue(RawValue $value)
    {
        $val = $value->getValue();

        if ($val instanceof DateIntervalValue) {
            return $val;
        }

        if (is_string($val) || $val instanceof \DateInterval) {
            return DateIntervalValue::createFromRaw($value);
        }

        return $this->getPrearrangedValue($value->getDefinition());
    }

    /**
     * @param \DateInterval|DateIntervalValue|string $value1
     * @param \DateInterval|DateIntervalValue|string $value2
     */
    public function valuesAreEqual($value1, $value2): bool
    {
        if (is_string($value1)) {
            try {
                $value1 = new \DateInterval($value1);
            } catch (\Exception $exception) {
                return false;
            }
        }
        if (is_string($value2)) {
            try {
                $value2 = new \DateInterval($value2);
            } catch (\Exception $exception) {
                return false;
            }
        }

        if (!($value1 instanceof \DateInterval) && !($value1 instanceof DateIntervalValue)) {
            return false;
        }
        if (!($value2 instanceof \DateInterval) && !($value2 instanceof DateIntervalValue)) {
            return false;
        }

        return $value1->format('%Y %M %D %H %I %S %F %R') === $value2->format('%Y %M %D %H %I %S %F %R');
    }

    /**
     * @return string
     */
    public function valueToRepository(RawValue $value)
    {
        /** @var DateIntervalValue $val */
        $val = $value->getValue();
        return $val->format('P%YY%MM%DD%HH%II%SS%FF%RR');
    }

    /**
     * @return DateIntervalValue
     */
    public function valueFromRepository(RawValue $value)
    {
        return DateIntervalValue::createFromRaw($value);
    }
}

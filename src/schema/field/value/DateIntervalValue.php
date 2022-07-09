<?php

namespace lx\model\schema\field\value;

use DateInterval;

/**
 * @property int $y
 * @property int $m
 * @property int $d
 * @property int $h
 * @property int $i
 * @property int $s
 * @property float $f
 * @property int $invert
 * @property int|false $days
 */
class DateIntervalValue extends ValueAsObject
{
    private DateInterval $interval;

    public function getDateInterval(): DateInterval
    {
        return $this->interval;
    }
    
    /**
     * @param DateInterval|string $value
     */
    protected function initValue($value): void
    {
        if ($value instanceof DateInterval) {
            $this->interval = $value;
            return;
        }

        try {
            $this->interval = new DateInterval($value);
        } catch (\Exception $exception) {
            //TODO on strict_mode(?) exception or warning?
            $this->setIfRequired();
        }
    }

    protected function prepareIfRequired(): void
    {
        $this->interval = new DateInterval('P1D');
    }

    public function format(string $format): ?string
    {
        return $this->interval ? $this->interval->format($format) : null;
    }

    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        if (in_array($name, ['y', 'm', 'd', 'h', 'i', 's', 'f', 'invert', 'days'])) {
            return $this->interval->$name;
        }
    }

    /**
     * @param mixed $value
     */
    public function __set(string $name, $value)
    {
        if (in_array($name, ['y', 'm', 'd', 'h', 'i', 's', 'f', 'invert', 'days'])) {
            $this->interval->$name = $value;
        }
    }
}

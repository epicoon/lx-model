<?php

namespace lx\model\schema\field\value;

use DateTime;

/**
 * @method static|null modify(string $modifier)
 * @method static|null setTime(int $hour, int $minute, int $second = 0, int $microsecond = 0)
 * @method static add(\DateInterval $interval)
 * @method static sub(\DateInterval $interval)
 * @method static setDate(int $year, int $month, int $day)
 * @method static setISODate(int $year, int $week, int $dayOfWeek = 1)
 * @method static setTimezone(\DateTimeZone $timezone)
 * @method static setTimestamp(int $timestamp)
 * @method \DateTimeZone|null getTimezone()
 * @method int|null getOffset()
 * @method int|null getTimestamp()
 * @method \DateInterval|null diff(\DateTimeInterface $targetObject, bool $absolute = false)
 */
class DateTimeValue extends ValueAsObject
{
    private DateTime $dateTime;

    /**
     * @param DateTime|string $value
     */
    public function init($value): void
    {
        if ($value instanceof DateTime) {
            $this->dateTime = $value;
            return;
        }

        try {
            $this->dateTime = new DateTime($value);
        } catch (\Exception $exception) {
            //TODO on strict_mode(?) exception or warning?
            $this->setIfRequired();
        }
    }

    public function prepareIfRequired(): void
    {
        $this->dateTime = new DateTime('1985-10-01 23:05');
    }
    
    public function format(string $format): ?string
    {
        return $this->dateTime ? $this->dateTime->format($format) : null;
    }

    /**
     * @return mixed
     */
    public function __call(string $methodName, array $arguments = [])
    {
        if (method_exists(DateTime::class, $methodName)) {
            $getters = ['getTimezone', 'getOffset', 'getTimestamp', 'diff'];
            if (in_array($methodName, $getters)) {
                if ($this->isNull()) {
                    return null;
                }
                $result = empty($arguments)
                    ? call_user_func([$this->dateTime, $methodName])
                    : call_user_func_array([$this->dateTime, $methodName], $arguments);
                if ($result === false) {
                    return null;
                }
                return $result;
            }

            if (!$this->dateTime) {
                $this->dateTime = new DateTime();
            }

            if ($methodName == 'modify' || $methodName == 'setTime') {
                $result = call_user_func_array([$this->dateTime, $methodName], $arguments);
                if ($result === false) {
                    return null;
                }
                return $this;
            }

            $methods = ['add', 'sub', 'setDate', 'setISODate', 'setTimezone', 'setTimestamp'];
            if (in_array($methodName, $methods)) {
                call_user_func_array([$this->dateTime, $methodName], $arguments);
                return $this;
            }
        }
        
        //TODO exception
        return null;
    }
}

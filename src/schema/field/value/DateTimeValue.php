<?php

namespace lx\model\schema\field\value;

use DateTime;
use lx\model\schema\field\definition\AbstractDefinition;

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

    public function getDateTime(): DateTime
    {
        return $this->dateTime;
    }

    protected function getCallInstructions(): array
    {
        return [
            'goalName' => 'dateTime',
            'getters' => ['getTimezone', 'getOffset', 'getTimestamp', 'diff'],
            'setters' => ['modify', 'setTime', 'add', 'sub', 'setDate', 'setISODate', 'setTimezone', 'setTimestamp'],
        ];
    }

    /**
     * @param DateTime|string $value
     */
    protected function initValue($value): void
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

    protected function prepareIfRequired(): void
    {
        $this->dateTime = new DateTime('1985-10-01 23:05');
    }
    
    public function format(string $format): ?string
    {
        return $this->dateTime ? $this->dateTime->format($format) : null;
    }
}

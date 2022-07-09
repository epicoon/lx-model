<?php

namespace lx\model\schema\field\value;

/**
 * @method static|null modify(string $modifier)
 * @method static|null setTime(int $hour, int $minute, int $second = 0, int $microsecond = 0)
 * @method static add(\DateInterval $interval)
 * @method static sub(\DateInterval $interval)
 * @method \DateInterval|null diff(\DateTimeInterface $targetObject, bool $absolute = false)
 */
class TimeValue extends ValueAsObject
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
            'getters' => ['diff'],
            'setters' => ['modify', 'setTime', 'add', 'sub'],
        ];
    }

    /**
     * @param DateTime|string $value
     */
    protected function initValue($value): void
    {
        if ($value instanceof DateTime) {
            $this->dateTime = $value;
        } else {
            try {
                $this->dateTime = new DateTime($value);
            } catch (\Exception $exception) {
                //TODO on strict_mode(?) exception or warning?
                $this->setIfRequired();
                return;
            }
        }

        $this->dateTime = new \DateTime($this->dateTime->format('H:i:s'));
    }

    protected function prepareIfRequired(): void
    {
        $this->dateTime = new DateTime('00:00');
    }

    public function format(string $format): ?string
    {
        return $this->dateTime ? $this->dateTime->format($format) : null;
    }
}

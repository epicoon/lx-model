<?php

namespace lx\model\schema\field\value;

/**
 * @method static|null modify(string $modifier)
 * @method static add(\DateInterval $interval)
 * @method static sub(\DateInterval $interval)
 * @method static setDate(int $year, int $month, int $day)
 * @method static setISODate(int $year, int $week, int $dayOfWeek = 1)
 * @method \DateInterval|null diff(\DateTimeInterface $targetObject, bool $absolute = false)
 */
class DateValue extends ValueAsObject
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
            'setters' => ['modify', 'add', 'sub', 'setDate', 'setISODate'],
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

        $this->dateTime = new \DateTime($this->dateTime->format('Y-m-d'));
    }

    protected function prepareIfRequired(): void
    {
        $this->dateTime = new DateTime('1985-10-01');
    }

    public function format(string $format): ?string
    {
        return $this->dateTime ? $this->dateTime->format($format) : null;
    }
}

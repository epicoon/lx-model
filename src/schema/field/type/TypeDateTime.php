<?php

namespace lx\model\schema\field\type;

class TypeDateTime extends Type
{
    public function getTypeName(): string
    {
        return 'datetime';
    }

    public function getPhpType(): string
    {
        return PhpTypeEnum::DATETIME;
    }

    /**
     * @param \DateTime $value
     */
    public function validateValue($value): bool
    {
        return $value instanceof \DateTime;
    }

    /**
     * @param \DateTime $value
     * @return \DateTime
     */
    public function normalizeValue($value)
    {
        return $value;
    }

    /**
     * @param \DateTime $value
     * @return string
     */
    public function valueToRepository($value)
    {
        //TODO кастомизировать форматирование
        return $value->format('Y-m-d h:i:s');
    }

    /**
     * @param string $value
     * @return \DateTime
     */
    public function valueFromRepository($value)
    {
        return new \DateTime($value);
    }
}

<?php

namespace lx\model\schema\field\definition;

use lx\model\schema\field\type\TypeDecimal;

class DecimalDefinition extends AbstractDefinition
{
    const DEFAULT_PRECISION = 18;
    const DEFAULT_SCALE = 5;

    private int $precision;
    private int $scale;

    public function init(array $definition): void
    {
        $this->precision = (int)($definition['precision'] ?? self::DEFAULT_PRECISION);
        $this->scale = (int)($definition['scale'] ?? self::DEFAULT_SCALE);
    }

    public function isEqual(AbstractDefinition $fieldDefinition): bool
    {
        if (!($fieldDefinition instanceof DecimalDefinition)) {
            return false;
        }

        return $this->precision == $fieldDefinition->precision
            && $this->scale == $fieldDefinition->scale;
    }

    public function toArray(): array
    {
        return [
            'precision' => $this->precision,
            'scale' => $this->scale,
        ];
    }

    public function toString(): string
    {
        return TypeDecimal::TYPE . "({$this->precision},{$this->scale})";
    }
}

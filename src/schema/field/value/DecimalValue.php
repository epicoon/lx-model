<?php

namespace lx\model\schema\field\value;

use lx\model\schema\field\definition\AbstractDefinition;
use lx\model\schema\field\definition\DecimalDefinition;

class DecimalValue extends ValueAsObject
{
    private int $wholePart;
    private int $fraction;
    private DecimalDefinition $definition;

    /**
     * @param string $value
     */
    protected function initValue($value): void
    {
        preg_match_all('/^(-?\d+?)\.(\d+?)$/', $value, $matches);
        if (empty($matches[0])) {
            //TODO on strict_mode(?) exception or warning?
            $this->setIfRequired();
            return;
        }

        $this->wholePart = (int)$matches[1][0];
        $this->fraction = (int)$matches[2][0];
    }
    
    protected function initDefinition(AbstractDefinition $definition): void
    {
        $this->definition = $definition;
    }

    public function prepareIfRequired(): void
    {
        $this->wholePart = 0;
        $this->fraction = 0;
    }

    public function getWholePart(): int
    {
        return $this->wholePart;
    }

    public function getFraction(): int
    {
        return $this->fraction;
    }

    public function getFractionSize(): int
    {
        return $this->definition->toArray()['scale'];
    }
    
    public function isNegative(): bool
    {
        return $this->wholePart < 0;
    }

    public function negate(): DecimalValue
    {
        $this->wholePart = -1 * $this->wholePart;
        return $this;
    }

    /**
     * @param DecimalValue|string $value
     */
    public function plus($value): DecimalValue
    {
        if (is_string($value)) {
            $value = new DecimalValue($this->definition, $value);
        }

        if ($value->isNegative()) {
            return $this->minus($value->negate());
        }

        $this->wholePart += $value->wholePart;
        $this->fraction += $value->fraction;
        $this->normalizeFraction();
        return $this;
    }

    /**
     * @param DecimalValue|string $value
     */
    public function minus($value): DecimalValue
    {
        if (is_string($value)) {
            $value = new DecimalValue($this->definition, $value);
        }

        if ($value->isNegative()) {
            return $this->plus($value->negate());
        }

        $this->wholePart -= $value->wholePart;
        $this->fraction -= $value->fraction;
        $this->normalizeFraction();
        return $this;
    }
    
    public function multiply(int $value): DecimalValue
    {
        $this->wholePart *= $value;
        $this->fraction *= $value;
        $this->normalizeFraction();
        return $this;
    }
    
    public function devide(int $value, ?callable $devideAlgorithm = null): DecimalValue
    {
        if ($devideAlgorithm) {
            list($wholePart, $fraction) = $devideAlgorithm($this->wholePart, $this->fraction, $value);
            $this->wholePart = $wholePart;
            $this->fraction = $fraction;
            return $this;
        }

        $residue = $this->wholePart % $value;
        $this->wholePart = floor($this->wholePart / $value);
        if ($residue) {
            $limit = (int)('1' . str_repeat('0', $this->getFractionSize()));
            $this->fraction += $limit * $residue;
        }
        $this->fraction = round($this->fraction / $value);
        $this->normalizeFraction();
        return $this;
    }
    
    public function toString(): string
    {
        $fraction = '' . $this->fraction;
        $realFractionSize = strlen($fraction);
        $fractionSize = $this->getFractionSize();
        if ($realFractionSize < $fractionSize) {
            $fraction = str_repeat('0', $fractionSize - $realFractionSize) . $fraction;
        }
        
        return $this->wholePart . '.' . $fraction;
    }

    private function normalizeFraction(): void
    {
        if ($this->fraction < 0) {
            $limit = (int)('1' . str_repeat('0', $this->getFractionSize()));
            $fraction = -1 * $this->fraction;
            $this->wholePart -= floor(($fraction + $limit) / $limit);
            $this->fraction = $limit - ($fraction % $limit);
            return;
        }

        $limit = (int)('1' . str_repeat('0', $this->getFractionSize()));
        if ($this->fraction < $limit) {
            return;
        }
        $newFraction = $this->fraction % $limit;
        $this->wholePart += (int)(($this->fraction - $newFraction) / $limit);
        $this->fraction = $newFraction;
    }
}

<?php

namespace lx\model\schema\field\value;

class DecimalValue extends ValueAsObject
{
    private int $wholePart;
    private int $fraction;
    private int $fractionSize;

    /**
     * @param string $value
     */
    public function init($value): void
    {
        preg_match_all('/^(\d+?)\.(\d+?)$/', $value, $matches);
        if (empty($matches[0])) {
            //TODO on strict_mode(?) exception or warning?
            $this->setIfRequired();
            return;
        }

        $this->wholePart = (int)$matches[1][0];
        $this->fraction = (int)$matches[2][0];
        $this->fractionSize = strlen($matches[2][0]);
    }

    public function prepareIfRequired(): void
    {
        $this->wholePart = 0;
        $this->fraction = 0;
        $this->fractionSize = 2;
    }

    public function getWholePart(): int
    {
        return $this->wholePart;
    }

    public function getFraction(): int
    {
        return $this->fraction;
    }


}

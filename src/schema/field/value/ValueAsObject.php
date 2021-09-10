<?php

namespace lx\model\schema\field\value;

abstract class ValueAsObject
{
    private bool $isNull = false;

    /**
     * @param mixed $value
     */
    public function __construct($value = null)
    {
        if ($value === null) {
            $this->isNull = true;
            return;
        }

        $this->init($value);
    }

    abstract public function prepareIfRequired(): void;

    /**
     * @param mixed $value
     */
    abstract public function init($value): void;

    public function setIfRequired(): void
    {
        $this->isNull = false;
        $this->prepareIfRequired();
    }

    /**
     * @param mixed $value
     */
    public function set($value): void
    {
        $this->isNull = false;
        $this->init($value);
    }

    public function isNull(): bool
    {
        return $this->isNull;
    }
}

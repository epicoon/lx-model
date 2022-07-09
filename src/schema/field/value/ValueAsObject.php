<?php

namespace lx\model\schema\field\value;

use lx\model\schema\field\definition\AbstractDefinition;
use lx\model\schema\field\RawValue;

abstract class ValueAsObject
{
    private bool $isNull = false;

    /**
     * @param mixed $value
     */
    public function __construct(AbstractDefinition $definition, $value = null)
    {
        $this->initDefinition($definition);

        if ($value === null) {
            $this->isNull = true;
            return;
        }

        $this->initValue($value);
    }

    public static function createFromRaw(RawValue $value): ValueAsObject
    {
        return new static($value->getDefinition(), $value->getValue());
    }

    abstract protected function prepareIfRequired(): void;

    /**
     * @param mixed $value
     */
    abstract protected function initValue($value): void;

    protected function initDefinition(AbstractDefinition $definition): void
    {
        // pass
    }

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
        $this->initValue($value);
    }

    public function isNull(): bool
    {
        return $this->isNull;
    }
}

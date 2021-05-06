<?php

namespace lx\model\repository\db\tools\holdStack;

use lx\model\Model;

class HoldStack
{
    private bool $_isActive;
    private array $stack;
    private array $list;

    public function __construct()
    {
        $this->_isActive = false;
        $this->stack = [];
        $this->list = [];
    }

    public function isActive(): bool
    {
        return $this->_isActive;
    }

    public function isFlat(): bool
    {
        return $this->_isActive && empty($this->stack);
    }

    public function mount(): void
    {
        if (!empty($this->list)) {
            $this->stack[] = $this->list;
            $this->list = [];
        } else {
            $this->stack[] = [];
        }
        $this->_isActive = true;
    }

    public function flatten(): void
    {
        if (!$this->_isActive) {
            return;
        }

        $count = count($this->stack);
        if ($count == 1) {
            $this->list = $this->stack[0];
            $this->stack = [];
            return;
        }

        $list = array_pop($this->stack);
        $this->stack[$count - 2] = array_merge($this->stack[$count - 2], $list);
    }

    public function pop(): array
    {
        if (!$this->_isActive || empty($this->stack)) {
            return [];
        }

        $result = array_pop($this->stack);
        if (empty($this->stack)) {
            $this->reset();
        }

        return $result;
    }

    public function add(Model $model): void
    {
        $count = count($this->stack);
        $this->stack[$count - 1][] = $model;
    }

    public function reset(): void
    {
        $this->_isActive = false;
        $this->stack = [];
        $this->list = [];
    }

    public function getList(): ?array
    {
        if ($this->isFlat()) {
            return $this->list;
        }

        return null;
    }
}

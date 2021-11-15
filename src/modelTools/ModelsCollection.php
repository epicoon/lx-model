<?php

namespace lx\model\modelTools;

use lx\ArrayInterface;
use lx\ArrayTrait;
use lx\ModelInterface;

class ModelsCollection implements ArrayInterface
{
    use ArrayTrait;

    public function __construct(iterable $collection = [])
    {
        $this->__constructArray($collection);
    }
    
    public function getModelsAsArray(): array
    {
        $result = [];
        /** @var ModelInterface $model */
        foreach ($this as $model) {
            $result[] = $model->getFields();
        }
        return $result;
    }
}

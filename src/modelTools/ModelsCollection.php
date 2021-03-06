<?php

namespace lx\model\modelTools;

use lx\ArrayInterface;
use lx\ArrayTrait;
use lx\model\Model;

/**
 * Class ModelCollection
 * @package lx\model\modelTools
 */
class ModelsCollection implements ArrayInterface
{
    use ArrayTrait;

    public function __construct(?iterable $collection = null)
    {
        $this->__constructArray($collection ?? []);
    }
}

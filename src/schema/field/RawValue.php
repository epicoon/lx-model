<?php

namespace lx\model\schema\field;

use lx\model\schema\field\definition\AbstractDefinition;

class RawValue
{
    /** @var mixed */
    private $value;
    private AbstractDefinition $definition;

    /**
     * @param mixed $value
     */
    public function __construct($value, AbstractDefinition $definition)
    {
        $this->value = $value;
        $this->definition = $definition;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
    
    public function getDefinition(): AbstractDefinition
    {
        return $this->definition;
    }
}

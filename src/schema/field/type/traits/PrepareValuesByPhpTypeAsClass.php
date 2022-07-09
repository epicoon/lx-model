<?php

namespace lx\model\schema\field\type\traits;

use lx\model\schema\field\definition\AbstractDefinition;

trait PrepareValuesByPhpTypeAsClass
{
    public function getValueIfRequired(AbstractDefinition $definition)
    {
        $class = $this->getPhpType();
        $value = new $class($definition);
        $value->setIfRequired();
        return $value;
    }

    public function getPrearrangedValue(AbstractDefinition $definition)
    {
        $class = $this->getPhpType();
        return new $class($definition);
    }
}

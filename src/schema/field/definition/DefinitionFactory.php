<?php

namespace lx\model\schema\field\definition;

use lx\model\schema\field\type\TypeDecimal;
use lx\model\schema\field\type\TypeString;

class DefinitionFactory
{
    public static function create(string $type): AbstractDefinition
    {
        switch ($type) {
            case TypeString::TYPE:
                return new StringDefinition();
            case TypeDecimal::TYPE:
                return new DecimalDefinition();
         
            default:
                return new CommonDefinition();
        }
    }
}

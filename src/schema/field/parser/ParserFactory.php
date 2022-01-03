<?php

namespace lx\model\schema\field\parser;

use lx\model\schema\field\type\TypeDecimal;
use lx\model\schema\field\type\TypeString;

class ParserFactory
{
    public static function create(string $type): CommonParser
    {
        switch ($type) {
            case TypeString::TYPE:
                return new StringParser();
            case TypeDecimal::TYPE:
                return new DecimalParser();
         
            default:
                return new CommonParser();
        }
    }
}

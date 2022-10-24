<?php

namespace lx\model\exception;

use Exception;
use Throwable;

class SchemaException extends Exception
{
    public function __construct($message = "")
    {
        parent::__construct($message, 500);
    }
}

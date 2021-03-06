<?php

namespace lx\model\schema\field\type;

/**
 * Class CommonTypesRegistry
 * @package lx\model\schema\field\type
 */
class CommonTypesRegistry extends TypesRegistry
{
    /**
     * @return void
     */
    protected function init()
    {
        $this->register(TypeString::class);
        $this->register(TypeInteger::class);
        $this->register(TypeBoolean::class);
        $this->register(TypeDictionary::class);
    }
}

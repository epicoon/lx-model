<?php

namespace lx\model\schema\field\type;

class CommonTypesRegistry extends TypesRegistry
{
    protected function init(): void
    {
        $this->register(TypeString::class);
        $this->register(TypeInteger::class);
        $this->register(TypeBoolean::class);
        $this->register(TypeDictionary::class);
    }
}

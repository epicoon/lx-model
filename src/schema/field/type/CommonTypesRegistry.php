<?php

namespace lx\model\schema\field\type;

class CommonTypesRegistry extends TypesRegistry
{
    protected function init(): void
    {
        $this->register(TypeString::class);
        $this->register(TypeDecimal::class);
        $this->register(TypeInteger::class);
        $this->register(TypeFloat::class);
        $this->register(TypeBoolean::class);
        $this->register(TypeDictionary::class);
        $this->register(TypeDateTime::class);
        $this->register(TypeDateInterval::class);
        $this->register(TypeDate::class);
        $this->register(TypeTime::class);
    }
}

<?php

namespace lx\model\schema\field\type;

interface TypesProviderInterface
{
    /**
     * Example:
     * return [
     *     'typeName' => TypeClass::class,
     * ];
     */
    public static function getTypes(): array;
}

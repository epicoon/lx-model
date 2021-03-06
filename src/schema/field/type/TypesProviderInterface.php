<?php

namespace lx\model\schema\field\type;

/**
 * Interface TypesProviderInterface
 * @package lx\model\schema\field\type
 */
interface TypesProviderInterface
{
    /**
     * Example:
     * return [
     *     'typeName' => TypeClass::class,
     * ];
     *
     * @return array
     */
    public static function getTypes(): array;
}

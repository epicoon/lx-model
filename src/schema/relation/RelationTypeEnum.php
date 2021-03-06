<?php

namespace lx\model\schema\relation;

class RelationTypeEnum
{
    const MANY_TO_MANY = 'manyToMany';
    const ONE_TO_MANY = 'oneToMany';
    const MANY_TO_ONE = 'manyToOne';
    const ONE_TO_ONE = 'oneToOne';

    /**
     * @param string $type
     * @return string|null
     */
    public static function getContrType($type)
    {
        switch ($type) {
            case self::ONE_TO_ONE:
                return self::ONE_TO_ONE;
            case self::ONE_TO_MANY:
                return self::MANY_TO_ONE;
            case self::MANY_TO_ONE:
                return self::ONE_TO_MANY;
            case self::MANY_TO_MANY:
                return self::MANY_TO_MANY;
        }

        return null;
    }
}

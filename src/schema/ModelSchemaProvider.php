<?php

namespace lx\model\schema;

class ModelSchemaProvider
{
    /** @var array<ModelSchema> */
    private static array $list = [];

    public static function getSchema(string $modelClass): ?ModelSchema
    {
        if (!array_key_exists($modelClass, self::$list)) {
            self::loadSchema($modelClass);
        }

        return self::$list[$modelClass] ?? null;
    }

    private static function loadSchema(string $modelClass): void
    {
        $schema = ModelSchema::createFromModelClass($modelClass);
        if ($schema) {
            self::$list[$modelClass] = $schema;
        }
    }
}

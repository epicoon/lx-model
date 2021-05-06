<?php

namespace lx\model\repository\db\tools;

use lx\model\schema\ModelSchema;

class SchemaBuffer
{
    /** @var array<ModelSchema> */
    private static array $schemaMap = [];

    public static function setModelSchema(string $modelName, ModelSchema $schema)
    {
        self::$schemaMap[$modelName] = $schema;
    }

    public static function getModelSchema(string $modelName): ?ModelSchema
    {
        return self::$schemaMap[$modelName] ?? null;
    }
}

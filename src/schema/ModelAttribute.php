<?php

namespace lx\model\schema;

abstract class ModelAttribute
{
    protected ModelSchema $schema;
    protected string $name;

    protected function __construct(ModelSchema $schema, string $name)
    {
        $this->schema = $schema;
        $this->name = $name;
    }

    public function getSchema(): ModelSchema
    {
        return $this->schema;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSetters(): array
    {
        return [];
    }

    public function getGetters(): array
    {
        return [];
    }

    public function getMethods(): array
    {
        return [];
    }

    abstract public function isEqual(ModelAttribute $attribute): bool;

    /**
     * @param mixed $currentValue
     * @return mixed
     */
    abstract public function callMethod(string $methodName, $currentValue, array $arguments);
}

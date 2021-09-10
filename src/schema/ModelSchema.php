<?php

namespace lx\model\schema;

use lx\model\Model;
use lx\model\schema\field\ModelField;
use lx\model\schema\relation\ModelRelation;
use lx\ModelSchemaInterface;
use lx\Service;

class ModelSchema implements ModelSchemaInterface
{
    private ?Service $service;
    private string $modelName;
    /** @var string&Model|null */
    private ?string $modelClassName;
    private ?array $array;

    /** @var array<ModelField> */
    private array $fields;
    /** @var array<ModelRelation> */
    private array $relations;

    private array $setters;
    private array $getters;
    private array $methods;

    /**
     * @param string&Model $modelClass
     */
    public static function createFromModelClass(string $modelClass): ?ModelSchema
    {
        $schemaArray = $modelClass::getSchemaArray();
        if (!array_key_exists('name', $schemaArray)) {
            return null;
        }

        $schema = new self();
        $schema->construct($schemaArray, $modelClass::getModelService());
        $schema->modelClassName = is_string($modelClass) ? $modelClass : get_class($modelClass);
        $schema->array = null;
        return $schema;
    }

    public static function createFromArray(array $schemaArray, ?Service $service): ModelSchema
    {
        $schema = new self();
        $schema->construct($schemaArray, $service);
        $schema->modelClassName = null;
        $schema->array = $schemaArray;
        return $schema;
    }

    public function toArray(): array
    {
        if ($this->modelClassName) {
            return $this->modelClassName::getSchemaArray();
        }

        if ($this->array) {
            return $this->array;
        }

        return [];
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function getModelName(): string
    {
        return $this->modelName;
    }

    public function getSetters(): array
    {
        return $this->setters;
    }

    public function getGetters(): array
    {
        return $this->getters;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function hasField(string $name): bool
    {
        return array_key_exists($name, $this->fields);
    }

    public function getField(string $name): ?ModelField
    {
        return $this->fields[$name] ?? null;
    }

    /**
     * @return array<ModelField>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function hasRelation(string $name): bool
    {
        return array_key_exists($name, $this->relations);
    }

    public function getRelation(string $name): ?ModelRelation
    {
        return $this->relations[$name] ?? null;
    }

    /**
     * @return array<ModelRelation>
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    public function hasMethod(string $name): bool
    {
        return array_key_exists($name, $this->methods);
    }

    public function getAttributeForMethod(string $name): ?ModelAttribute
    {
        return $this->methods[$name] ?? null;
    }

    private function construct(array $schemaArray, ?Service $service = null): void
    {
        $this->service = $service;
        $this->modelName = $schemaArray['name'];
        $this->modelClassName = $schemaArray['className'] ?? null;

        $this->fields = [];
        $this->relations = [];
        $this->setters = [];
        $this->getters = [];
        $this->methods = [];

        $this->fields = [];
        $fields = $schemaArray['fields'] ?? [];
        foreach ($fields as $fieldName => $fieldDefinition) {
            $field = new ModelField($this, $fieldName, $fieldDefinition);
            $this->fields[$fieldName] = $field;
            $this->setters = array_merge($this->setters, $field->getSetters());
            $this->getters = array_merge($this->getters, $field->getGetters());
            $this->methods = array_merge($this->methods, $field->getMethods());
        }

        $relations = $schemaArray['relations'] ?? [];
        foreach ($relations as $relationName => $relationDefinition) {
            $relation = new ModelRelation($this, $relationName, $relationDefinition);
            $this->relations[$relationName] = $relation;
            $this->setters = array_merge($this->setters, $relation->getSetters());
            $this->getters = array_merge($this->getters, $relation->getGetters());
            $this->methods = array_merge($this->methods, $relation->getMethods());
        }
    }
}

<?php

namespace lx\model;

use lx;
use lx\DataObject;
use lx\ModelInterface;
use lx\model\modelTools\ModelRelationKeeper;
use lx\model\repository\RepositoryInterface;
use lx\model\schema\ModelSchema;
use lx\model\schema\ModelSchemaProvider;
use lx\Service;

abstract class Model implements ModelInterface
{
    private static $nullCache = null;

    private ?int $_id;

    private array $_fields;
    private array $_oldFields;

    /** @var array<ModelRelationKeeper> */
    private array $_relations;

    private ?DataObject $_metaData;

    public function __construct(array $fields = [])
    {
        $this->_id = null;
        $this->_fields = [];
        $this->_oldFields = [];
        $this->_relations = [];
        $this->_metaData = null;

        $schema = $this->getSchema();
        if (!$schema) {
            return;
        }

        foreach ($fields as $name => $field) {
            if ($schema->hasField($name)) {
                $this->setField($name, $field);
            } elseif ($schema->hasRelation($name)) {
                $this->setRelatedKey($name, $field);
            }
        }

        foreach ($schema->getFields() as $field) {
            if ($field->getDefault() !== null && ($this->_fields[$field->getName()] ?? null) === null) {
                $this->setField($field->getName(), $field->getDefault());
            }
        }
    }

    /**
     * @param mixed $value
     */
    public function __set(string $name, $value)
    {
        $schema = $this->getSchema();
        if ($schema->hasField($name)) {
            $this->setField($name, $value);
            return;
        }

        if ($schema->hasRelation($name)) {
            $this->setRelated($name, $value);
        }
    }

    /**
     * @return mixed
     */
    public function &__get(string $name)
    {
        $schema = $this->getSchema();
        if ($schema->hasField($name)) {
            return $this->getField($name);
        }

        if ($schema->hasRelation($name)) {
            return $this->getRelated($name);
        }

        return $this->null();
    }

    public function __call(string $methodName, array $arguments)
    {
        $schema = $this->getSchema();
        if (!$schema->hasMethod($methodName)) {
            //TODO Exception
            return;
        }

        $attribute = $schema->getAttributeForMethod($methodName);
        $attributeName = $attribute->getName();
        if ($schema->hasField($attributeName)) {
            $result = $attribute->callMethod($methodName, $this->_fields[$attributeName] ?? null, $arguments);
            $this->setField($attributeName, $result);
        }
    }

    public function isNew(): bool
    {
        return $this->_id === null;
    }

    public function isChanged(): bool
    {
        if ($this->fieldsChanged()) {
            return true;
        }

        foreach ($this->_relations as $relation) {
            if ($relation->isChanged()) {
                return true;
            }
        }

        return false;
    }

    public function fieldsChanged(): bool
    {
        if ($this->isNew() || !empty($this->_oldFields)) {
            return true;
        }

        $schema = $this->getSchema();
        foreach ($this->_relations as $relationName => $relationKeeper) {
            if ($schema->getRelation($relationName)->isFkHolder() && $relationKeeper->isChanged()) {
                return true;
            }
        }

        return false;
    }

    public function reset(): void
    {
        foreach ($this->_oldFields as $key => $value) {
            $this->_fields[$key] = $this->_oldFields[$key];
        }
        $this->_oldFields = [];
    }

    public function dropId(): void
    {
        $this->_id = null;
        $this->_oldFields = [];
    }

    public function commitChanges(): void
    {
        $this->_oldFields = [];
        foreach ($this->_relations as $relation) {
            $relation->commitChanges();
        }
    }

    public function clone(): Model
    {
        $clone = new static();
        $clone->_fields = $this->_fields;
        return $clone;
    }

    public function hasField(string $name): bool
    {
        $schema = $this->getSchema();
        return $schema->hasField($name);
    }

    /**
     * @param mixed $value
     */
    public function setField(string $name, $value): void
    {
        $schema = $this->getSchema();
        if (!$schema->hasField($name)) {
            //TODO Exception
            return;
        }

        $fieldDef = $schema->getField($name);
        if (!$fieldDef->validateValue($value)) {
            //TODO Exception
            return;
        }

        $value = $fieldDef->normalizeValue($value);
        if (array_key_exists($name, $this->_fields) && $this->_fields[$name] == $value) {
            return;
        }

        $this->actualizeOldField($name, $value);
        $this->_fields[$name] = $value;
    }

    public function setFields(array $fields): void
    {
        foreach ($fields as $key => $value) {
            $this->setField($key, $value);
        }
    }

    /**
     * @return mixed
     */
    public function &getField(string $name)
    {
        if (array_key_exists($name, $this->_fields)) {
            return $this->_fields[$name];
        }

        return $this->null();
    }

    public function getFields(): array
    {
        return $this->_fields;
    }

    public function getId(): ?int
    {
        return $this->_id;
    }

    public function setId(int $id): void
    {
        if ($this->_id === null) {
            $this->_id = $id;
        }
    }

    public function getMetaData(): DataObject
    {
        if ($this->_metaData === null) {
            $this->_metaData = new DataObject();
        }

        return $this->_metaData;
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * REPOSITORY
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    public static function getCount(): int
    {
        return self::getModelRepository()->getCount(self::getStaticModelName());
    }

    public static function findOne($condition, bool $useUnitMap = true): ?Model
    {
        return self::getModelRepository()->findModel(
            self::getStaticModelName(),
            $condition,
            $useUnitMap
        );
    }

    /**
     * @return array<Model>
     */
    public static function find(?array $condition = null): array
    {
        return self::getModelRepository()->findModels(
            self::getStaticModelName(),
            $condition
        );
    }

    public function save(): bool
    {
        if (!$this->isChanged()) {
            return true;
        }

        return $this->getRepository()->saveModel($this);
    }

    public function delete(): bool
    {
        if ($this->isNew()) {
            return true;
        }

        return $this->getRepository()->deleteModel($this);
    }

    public static function deleteAll(?array $condition = null): void
    {
        self::getModelRepository()->deleteModelsByCondition(self::getStaticModelName(), $condition);
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * RELATIONS
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    public function hasRelation(string $name): bool
    {
        $schema = $this->getSchema();
        return $schema->hasRelation($name);
    }

    /**
     * @return mixed
     */
    public function &getRelated(string $name)
    {
        //TODO ??? нужна пагинация для toMany

        $schema = $this->getSchema();
        if (!$schema->hasRelation($name)) {
            return $this->null();
        }

        return $this->getRelationKeeper($name)->getRelated();
    }

    public function setRelated(string $name, ?ModelInterface $model): void
    {
        $this->getRelationKeeper($name)->pushModel($model);
    }

    public function removeRelated(string $name, ?ModelInterface $model = null): void
    {
        $this->getRelationKeeper($name)->dropModel($model);
    }

    public function clearRelated(string $name): void
    {
        //TODO оптимизировать?
        $allRelated = $this->getRelated($name);
        foreach ($allRelated as $relatedModel) {
            $this->removeRelated($name, $relatedModel);
        }
    }

    public function clearAllRelated(): void
    {
        $schema = $this->getSchema();
        foreach ($schema->getRelations() as $relationName => $relation) {
            $this->clearRelated($relationName);
        }
    }

    public function isRelationLoaded(string $name): bool
    {
        return array_key_exists($name, $this->_relations);
    }

    public function getRelationChanges(): array
    {
        $result = [];
        foreach ($this->_relations as $relationName => $relation) {
            $changes = $relation->getChanges();
            if (!empty($changes)) {
                $result[$relationName] = $changes;
            }
        }

        return $result;
    }

    /**
     * @return null|int|int[]
     */
    public function getRelatedKey(string $name)
    {
        return $this->getRelationKeeper($name)->getKey();
    }

    /**
     * @param int|int[] $key
     */
    private function setRelatedKey(string $name, $key): void
    {
        $this->getRelationKeeper($name)->setKey($key);
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * STATIC GETTERS
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    public static function getModelClassName(string $fullModelName): ?string
    {
        if (class_exists($fullModelName) && is_subclass_of($fullModelName, self::class)) {
            return $fullModelName;
        }

        if (strpos($fullModelName, '.')) {
            $nameArr = explode('.', $fullModelName);
            if (count($nameArr) != 2) {
                return null;
            }

            $service = lx::$app->getService($nameArr[0]);
            if (!$service) {
                return null;
            }

            $modelManager = $service->modelManager;
            if (!$modelManager) {
                return null;
            }

            return $modelManager->getModelClassName($nameArr[1]);
        }

        return null;
    }

    abstract public static function getServiceName(): string;

    abstract public static function getSchemaArray(): array;

    public static function getModelSchema(): ?ModelSchema
    {
        return ModelSchemaProvider::getSchema(static::class);
    }

    public function getSchema(): ?ModelSchema
    {
        return self::getModelSchema();
    }

    public static function getModelService(): ?Service
    {
        return lx::$app->getService(static::getServiceName());
    }

    public function getService(): ?Service
    {
        return self::getModelService();
    }

    public static function getStaticModelName(): string
    {
        return self::getModelSchema()->getModelName();
    }

    public function getModelName(): string
    {
        return $this->getSchema()->getModelName();
    }

    public static function getModelRepository(): ?RepositoryInterface
    {
        $service = self::getModelService();
        if (!$service) {
            return null;
        }

        $modelManager = $service->modelManager;
        if (!$modelManager) {
            return null;
        }

        return $modelManager->getRepository();
    }

    public function getRepository(): ?RepositoryInterface
    {
        $service = $this->getService();
        if (!$service) {
            return null;
        }

        $modelManager = $service->modelManager;
        if (!$modelManager) {
            return null;
        }

        return $modelManager->getRepository();
    }

    public static function createAnonymousModel(Service $service, ModelSchema $schema, array $fields): Model
    {
        return new class($service, $schema, $fields) extends Model {
            private Service $_service;
            private ModelSchema $_schema;
            public function __construct(Service $service, ModelSchema $schema, array $fields)
            {
                $this->_service = $service;
                $this->_schema = $schema;
                parent::__construct($fields);
            }
            public static function getServiceName(): string
            {
                return '';
            }
            public static function getSchemaArray(): array
            {
                return [];
            }
            public static function getModelSchema(): ?ModelSchema
            {
                return null;
            }
            public function getSchema(): ?ModelSchema
            {
                return $this->_schema;
            }
            public static function getModelService(): ?Service
            {
                return null;
            }
            public function getService(): ?Service
            {
                return $this->_service;
            }
        };
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * @param mixed $value
     */
    private function actualizeOldField(string $name, $value): void
    {
        if ($this->isNew()) {
            return;
        }

        if (array_key_exists($name, $this->_oldFields)) {
            if ($this->_oldFields[$name] == $value) {
                unset($this->_oldFields[$name]);
            }

            return;
        }

        if (array_key_exists($name, $this->_fields)) {
            $this->_oldFields[$name] = $this->_fields[$name];
        } else {
            $this->_oldFields[$name] = null;
        }
    }

    private function getRelationKeeper(string $name): ModelRelationKeeper
    {
        if (!array_key_exists($name, $this->_relations)) {
            $this->_relations[$name] = ModelRelationKeeper::create($this, $name);
        }

        return $this->_relations[$name];
    }

    private function & null()
    {
        self::$nullCache = null;
        return self::$nullCache;
    }
}

<?php

namespace lx\model\modelTools;

use lx\ModelInterface;
use lx\model\Model;
use lx\model\modelTools\relationModelWrapper\RelationModelWrapper;
use lx\model\schema\relation\ModelRelation;

abstract class ModelRelationKeeper
{
    protected Model $model;
    protected string $name;
    /** @var Model|RelatedModelsCollection|null */
    protected $related;
    protected bool $isLoaded;

    public static function create(Model $model, string $name)
    {
        $schema = $model->getSchema();
        $relation = $schema->getRelation($name);
        if ($relation->isToOne()) {
            return new ModelRelationKeeperToOne($model, $name);
        } else {
            return new ModelRelationKeeperToMany($model, $name);
        }
    }

    protected function __construct(Model $model, string $name)
    {
        $this->model = $model;
        $this->name = $name;
        $this->init();

        $this->isLoaded = false;
    }

    abstract protected function init(): void;
    /**
     * @param int|int[] $key
     */
    abstract public function setKey($key): void;
    /**
     * @return null|int|int[]
     */
    abstract public function getKey();
    abstract public function pushModel(?ModelInterface $model): void;
    abstract public function dropModel(?ModelInterface $model = null): void;
    abstract public function isChanged(): bool;
    abstract public function getChanges(): array;
    abstract public function commitChanges(): void;

    /**
     * @return Model|ModelsCollection|null
     */
    public function &getRelated()
    {
        if (!$this->model->isNew() && !$this->isLoaded) {
            $this->load();
        }

        return $this->related;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function getRelation(): ModelRelation
    {
        return $this->model->getSchema()->getRelation($this->name);
    }

    public function unpackModel(?Model $model): ?Model
    {
        if ($model instanceof RelationModelWrapper) {
            return $model->getModel();
        }

        return $model;
    }

    protected function getRelAttribute(): ?string
    {
        return $this->getRelation()->getRelatedAttributeName();
    }

    protected function load(): void
    {
        $this->model->getRepository()->findRelatedModels($this->model, $this->getRelation()->getName());
        $this->isLoaded = true;
    }
}

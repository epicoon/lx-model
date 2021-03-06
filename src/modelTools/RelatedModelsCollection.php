<?php

namespace lx\model\modelTools;

use lx\model\Model;
use lx\model\modelTools\relationModelWrapper\RelationModelSetter;

/**
 * Class RelatedModelsCollection
 * @package lx\model\modelTools
 */
class RelatedModelsCollection extends ModelsCollection
{
    private ModelRelationKeeperToMany $relationKeeper;

    public function __construct(ModelRelationKeeperToMany $relationKeeper)
    {
        parent::__construct();
        $this->relationKeeper = $relationKeeper;
    }

    public function removeModel(Model $model)
    {
        if (!$this->contains($this->relationKeeper->unpackModel($model))) {
            return;
        }

        $this->relationKeeper->onCollectionDeleting($model);
        $this->removeValue($this->relationKeeper->unpackModel($model));
    }

    /**
     * @return ?Model
     */
    public function pop()
    {
        if ($this->isEmpty()) {
            return null;
        }

        $this->relationKeeper->onCollectionDeleting($this[$this->len() - 1]);
        return parent::pop();
    }

    /**
     * @return ?Model
     */
    public function shift()
    {
        if ($this->isEmpty()) {
            return null;
        }

        $this->relationKeeper->onCollectionDeleting($this[0]);
        return parent::shift();
    }

    /**
     * @param Model[] $iterable
     * @return iterable
     */
    public function merge($iterable)
    {
        $list = [];
        foreach ($iterable as $model) {
            if ($this->contains($model)) {
                continue;
            }

            $list[] = $model;
            $this->relationKeeper->onCollectionAdding($model);
        }

        return parent::merge($list);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return bool
     */
    protected function beforeUnset($key, $value)
    {
        $this->relationKeeper->onCollectionDeleting($value);
        return true;
    }

    /**
     * @param int $key
     * @param Model $value
     * @return bool
     */
    protected function beforeSet($key, $value)
    {
        if ($this->contains($this->relationKeeper->unpackModel($value))) {
            return false;
        }

        if ($value instanceof RelationModelSetter) {
            $this->offsetSetProcess($key, $value->getModel());
            return false;
        }

        $this->relationKeeper->onCollectionAdding($value);
        $this->offsetSetProcess($key, $this->relationKeeper->unpackModel($value));
        return false;
    }
}

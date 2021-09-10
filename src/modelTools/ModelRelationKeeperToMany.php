<?php

namespace lx\model\modelTools;

use lx\ModelInterface;
use lx\model\Model;
use lx\model\modelTools\relationModelWrapper\RelationModelConnector;
use lx\model\modelTools\relationModelWrapper\RelationModelEraser;

class ModelRelationKeeperToMany extends ModelRelationKeeper
{
    /** @var int[] */
    private array $keys;
    private array $oldRelated = [];
    private array $newRelated = [];

    protected function init(): void
    {
        $this->keys = [];
        $this->related = new RelatedModelsCollection($this);
        $this->oldRelated = [];
        $this->newRelated = [];
    }

    /**
     * @param int|int[] $key
     */
    public function setKey($key): void
    {
        $this->keys = array_values(array_unique(array_merge($this->keys, (array)$key)));
    }

    /**
     * @return int[]
     */
    public function getKey()
    {
        return $this->keys;
    }

    public function pushModel(?ModelInterface $model): void
    {
        if ($model === null) {
            return;
        }

        $this->related[] = $model;
    }

    public function dropModel(?ModelInterface $model = null): void
    {
        if (!$model) {
            return;
        }

        //TODO пооптимальнее можно подумать
        if (!$this->isLoaded) {
            $this->load();
        }

        $this->related->removeModel($model);
    }

    public function isChanged(): bool
    {
        return !empty($this->newRelated) || !empty($this->oldRelated);
    }

    public function getChanges(): array
    {
        if (!$this->isChanged()) {
            return [];
        }

        return [
            'added' => $this->newRelated,
            'deleted' => $this->oldRelated,
        ];
    }

    public function commitChanges(): void
    {
        $this->oldRelated = [];
        $this->newRelated = [];
    }

    public function onCollectionAdding(Model $model): void
    {
        $this->newRelated[] = $this->unpackModel($model);

        if ($model instanceof RelationModelConnector) {
            return;
        }

        $this->getRelAttribute()
            && $model->setRelated($this->getRelAttribute(), new RelationModelConnector($this->model));
    }

    public function onCollectionDeleting(Model $model): void
    {
        $unpackedModel = $this->unpackModel($model);
        if (in_array($unpackedModel, $this->newRelated)) {
            $key = array_search($unpackedModel, $this->newRelated);
            unset($this->newRelated[$key]);
        } else {
            $this->oldRelated[] = $unpackedModel;
        }

        if ($model instanceof RelationModelEraser) {
            return;
        }

        /*isOneToMany*/
        if ($this->getRelation()->isOneToMany()) {
            $this->getRelAttribute() && $model->removeRelated(
                $this->getRelAttribute(),
                new RelationModelEraser(null)
            );
            return;
        }

        /*isManyToMany*/
        $this->getRelAttribute() && $model->removeRelated(
            $this->getRelAttribute(),
            new RelationModelEraser($this->model)
        );
    }
}

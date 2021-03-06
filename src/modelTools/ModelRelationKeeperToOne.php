<?php

namespace lx\model\modelTools;

use lx\ModelInterface;
use lx\model\Model;
use lx\model\modelTools\relationModelWrapper\RelationModelConnector;
use lx\model\modelTools\relationModelWrapper\RelationModelEraser;
use lx\model\modelTools\relationModelWrapper\RelationModelSetter;

/**
 * Class ModelRelationKeeperNoOne
 * @package lx\model\modelTools
 */
class ModelRelationKeeperToOne extends ModelRelationKeeper
{
    private ?int $key;
    private ?int $oldKey;
    protected ?Model $oldRelated;

    protected function init(): void
    {
        $this->key = 0;
        $this->oldKey = 0;
        $this->related = null;
        $this->oldRelated = null;
    }

    /**
     * @param int|null $key
     */
    public function setKey($key): void
    {
        $this->key = $key;
        $this->oldKey = $key;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function pushModel(?ModelInterface $model): void
    {
        if ($model instanceof RelationModelSetter) {
            $this->related = $model->getModel();
            $this->key = $this->related->getId();
            $this->oldKey = $this->key;
            return;
        }

        $this->dropModel();
        $this->related = $this->unpackModel($model);
        $this->key = $this->related ? $this->related->getId() : null;
        if ($this->key === $this->oldKey) {
            $this->oldRelated = null;
        }

        if ($model instanceof RelationModelConnector) {
            return;
        }

        if ($this->related && $this->getRelAttribute()) {
            $this->related->setRelated($this->getRelAttribute(), new RelationModelConnector($this->model));
        }
    }

    public function dropModel(?ModelInterface $model = null): void
    {
        if ($model instanceof RelationModelEraser) {
            if ($this->related->getId() == $this->oldKey) {
                $this->oldRelated = $this->related;
            }
            $this->related = null;
            $this->key = null;
            return;
        }

        if (!$this->oldKey) {
            return;
        }

        if (!$this->isLoaded) {
            $this->load();
        }

        $model = $this->related;
        $this->related = null;
        $this->key = null;

        if ($model->getId() == $this->oldKey) {
            $this->oldRelated = $model;
        }

        /*isManyToOne*/
        if ($this->getRelation()->isManyToOne()) {
            $this->getRelAttribute() && $model->removeRelated(
                $this->getRelAttribute(),
                new RelationModelEraser($this->model)
            );
            return;
        }

        /*isOneToOne*/
        $this->getRelAttribute() && $model->removeRelated(
            $this->getRelAttribute(),
            new RelationModelEraser(null)
        );
    }

    public function isChanged(): bool
    {
        return $this->oldRelated || $this->oldKey !== $this->key;
    }

    public function getChanges(): array
    {
        if (!$this->isChanged()) {
            return [];
        }

        return [
            'added' => ($this->related ? [$this->related] : []),
            'deleted' => ($this->oldRelated ? [$this->oldRelated] : []),
        ];
    }

    public function commitChanges(): void
    {
        $this->oldRelated = null;
        $this->oldKey = $this->key;
    }
}

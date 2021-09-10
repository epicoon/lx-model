<?php

namespace lx\model\repository\db\tools;

use lx\model\Model;

class UnitMap
{
    private array $map = [];

    public function get(string $modelName, int $id): ?Model
    {
        return $this->map[$modelName][$id] ?? null;
    }

    public function register(Model $model): void
    {
        $modelName = $model->getModelName();
        if (!array_key_exists($modelName, $this->map)) {
            $this->map[$modelName] = [];
        }

        $this->map[$modelName][$model->getId()] = $model;
    }

    public function unregister(Model $model, int $id): void
    {
        $modelName = $model->getModelName();
        if (!isset($this->map[$modelName][$id])) {
            return;
        }

        unset($this->map[$modelName][$id]);
        if (empty($this->map[$modelName])) {
            unset($this->map[$modelName]);
        }
    }

    /**
     * @param int[] $ids
     * @return array<Model>
     */
    public function getList(string $modelName, array $ids): array
    {
        $result = [];
        foreach ($ids as $id) {
            $model = $this->get($modelName, $id);
            if ($model) {
                $result[] = $model;
            }
        }

        return $result;
    }

    /**
     * @param iterable<Model> $models
     */
    public function registerList(iterable $models): void
    {
        foreach ($models as $model) {
            $this->register($model);
        }
    }

    public function unregisterList(iterable $list): void
    {
        foreach ($list as $item) {
            $this->unregister($item['model'], $item['id']);
        }
    }

    public function unregisterByModelName(string $modelName, array $ids): void
    {
        if (!array_key_exists($modelName, $this->map)) {
            return;
        }

        foreach ($ids as $id) {
            unset($this->map[$modelName][$id]);
        }

        if (empty($this->map[$modelName])) {
            unset($this->map[$modelName]);
        }
    }
}

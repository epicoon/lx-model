<?php

namespace lx\model\modelTools\relationModelWrapper;

use lx\model\Model;

/**
 * Class RelationModelWrapper
 * @package lx\model\modelTools\relationModelWrapper
 */
class RelationModelWrapper extends Model
{
    private ?Model $model;

    public function __construct(?Model $model)
    {
        parent::__construct();
        $this->model = $model;
    }

    public function getModel(): ?Model
    {
        return $this->model;
    }

    public static function getServiceName(): string
    {
        return '';
    }

    public static function getSchemaArray(): array
    {
        return [];
    }
}

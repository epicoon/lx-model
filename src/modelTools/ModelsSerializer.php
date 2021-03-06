<?php

namespace lx\model\modelTools;

use lx\model\Model;

/**
 * Class ModelsSerializer
 * @package lx\model\modelTools
 */
class ModelsSerializer
{
    /**
     * @param Model[] $models
     * @return array
     */
    public function collectionToArray(iterable $models): array
    {
        $result = [];
        foreach ($models as $model) {
            $fields = $model->getFields();
            $fields['id'] = $model->getId();
            $result[] = $fields;
        }

        return $result;
    }
}

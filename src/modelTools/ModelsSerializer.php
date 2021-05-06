<?php

namespace lx\model\modelTools;

use lx\model\Model;

class ModelsSerializer
{
    /**
     * @param iterable<Model> $models
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

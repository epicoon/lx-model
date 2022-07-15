<?php

namespace lx\model\managerTools\refresher;

use lx\CascadeReport;

/**
 * @method addToWrongModelNames(string $modelName)
 * @method addListToWrongModelNames(array $modelNames)
 * @method addToErrors(string $modelName)
 * @method addListToErrors(array $modelNames)
 * @method addToModelsNeedUpdate(string $modelName)
 * @method addListToModelsNeedUpdate(array $modelNames)
 * @method addToModelsCreated(string $modelName, string $modelClassName)
 * @method addListToModelsCreated(array $dict)
 * @method addToMediatorCreated(string $modelName, string $mediatorName)
 * @method addListToMediatorCreated(array $dict)
 * @method addToMediatorUpdated(string $modelName, string $mediatorName)
 * @method addListToMediatorUpdated(array $dict)
 */
class RefreshReport extends CascadeReport
{
    protected function getDataComponents(): array
    {
        return [
            'modelsNeedUpdate' => CascadeReport::COMPONENT_LIST,
            'modelsCreated' => CascadeReport::COMPONENT_DICT,
            'mediatorCreated' => CascadeReport::COMPONENT_DICT,
            'mediatorUpdated' => CascadeReport::COMPONENT_DICT,

            'wrongModelNames' => CascadeReport::COMPONENT_LIST,
            'errors' => CascadeReport::COMPONENT_LIST,
        ];
    }
}

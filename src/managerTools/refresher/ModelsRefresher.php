<?php

namespace lx\model\managerTools\refresher;

use lx\File;
use lx\model\managerTools\Conductor;
use lx\model\managerTools\ModelsContext;

/**
 * Class ModelsRefresher
 */
class ModelsRefresher
{
    const OPTION_PREVIEW = 'preview';

    private ModelsContext $context;
    private RefreshReport $report;

    public function __construct(ModelsContext $context)
    {
        $this->context = $context;
        $this->report = new RefreshReport();
    }

    public function compare(?array $modelNames = null): RefreshReport
    {
        $this->process($modelNames, true);
        return $this->report;
    }

    public function run(?array $modelNames = null): RefreshReport
    {
        $this->process($modelNames);
        return $this->report;
    }

    private function process(?array $modelNames = null, bool $preview = false): RefreshReport
    {
        $conductor = $this->context->getConductor();

        if (is_array($modelNames)) {
            $validatedNames = $conductor->validateModelNames($modelNames);
            $wrongNames = array_diff($modelNames, $validatedNames);
            $this->report->addListToWrongModelNames($wrongNames);
            $modelNames = $validatedNames;
        } else {
            $modelNames = $conductor->getAllModelNames();
        }

        $modelNamesProvider = new ModelNamesProvider($this->context);

        foreach ($modelNames as $modelName) {
            $refresher = new ModelRefresher($this->context, $modelName, $modelNamesProvider);
            $report = $refresher->run($preview ? [self::OPTION_PREVIEW] : []);
            $this->report->add($report);
        }

        $arr = $this->report->toArray();
        if (!empty($arr['modelsCreated'])) {
            /** @var File $mapFile */
            $mapFile = $conductor->getModelClassesMapFile();
            $map = ($mapFile && $mapFile->exists())
                ? json_decode($mapFile->get(), true)
                : [];

            $map = array_merge($map, $arr['modelsCreated']);
            if (!$mapFile) {
                $mapFile = $conductor->getModelMediatorsDirectory()->makeFile(Conductor::MODEL_CLASSES_MAP_FILE);
            }

            $mapFile->put($map);
        }

        return $this->report;
    }
}

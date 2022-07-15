<?php

namespace lx\model;

use lx\model\schema\field\type\CommonTypesRegistry;

/**
 * @property-read CommonTypesRegistry $typesRegistry
 */
class Service extends \lx\Service
{
    public function getDefaultFusionComponents(): array
    {
        return array_merge(parent::getDefaultFusionComponents(), [
            'typesRegistry' => CommonTypesRegistry::class,
        ]);
    }
}

<?php

namespace lx\model\schema\field\type;

use lx;
use lx\Service;

/**
 * Trait TypesRegistryTrait
 * @package lx\model\schema\field\type
 *
 * Class with this trait ought to implement method [[getService()]] to provide its service
 */
trait TypesRegistryTrait
{
    public function getTypeByName(string $name): ?Type
    {
        $typesRegistry = $this->getTypesRegistry();
        if ($typesRegistry && $typesRegistry->hasType($name)) {
            return $typesRegistry->getType($name);
        }

        return $this->getCommonTypesRegistry()->getType($name);
    }

    public function getTypeNames(): array
    {
        $typesRegistry = $this->getTypesRegistry();
        $names = $typesRegistry ? $typesRegistry->getTypeNames() : [];
        return array_values(
            array_unique(array_merge($this->getCommonTypesRegistry()->getTypeNames(), $names))
        );
    }

    public function getServiceTypeByName(Service $service, string $name): ?Type
    {
        $typesRegistry = $this->getServiceTypesRegistry($service);
        if ($typesRegistry && $typesRegistry->hasType($name)) {
            return $typesRegistry->getType($name);
        }

        return $this->getCommonTypesRegistry()->getType($name);
    }

    public function getServiceTypeNames(Service $service): array
    {
        $typesRegistry = $this->getServiceTypesRegistry($service);
        $names = $typesRegistry ? $typesRegistry->getTypeNames() : [];
        return array_values(
            array_unique(array_merge($this->getCommonTypesRegistry()->getTypeNames(), $names))
        );
    }

    private function getTypesRegistry(): ?TypesRegistry
    {
        if (!method_exists($this, 'getService')) {
            return null;
        }

        return $this->getServiceTypesRegistry($this->getService());
    }

    private function getServiceTypesRegistry(Service $service): ?TypesRegistry
    {
        if (!$service->hasFusionComponent('typesRegistry')) {
            return null;
        }

        $typesRegistry = $service->typesRegistry;
        if ($typesRegistry instanceof TypesRegistry) {
            return $typesRegistry;
        }

        return null;
    }

    private function getCommonTypesRegistry(): CommonTypesRegistry
    {
        /** @var lx\model\Service $service */
        $service = lx::$app->getService('lx/model');
        return $service->typesRegistry;
    }
}

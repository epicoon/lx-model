<?php

namespace lx\model\schema\field\type;

use lx\FusionComponentTrait;
use lx\ObjectTrait;

/**
 * Class TypesRegistry
 * @package lx\model\schema\field\type
 */
abstract class TypesRegistry
{
    use ObjectTrait;
    use FusionComponentTrait;

    /** @var Type[] */
    private array $typesMap = [];

    public function __construct(array $config = [])
    {
        $this->__objectConstruct($config);
        $this->init();
        $this->initProviders($config['providers'] ?? []);
    }

    abstract protected function init();

    public function getTypeNames(): array
    {
        return array_keys($this->typesMap);
    }

    public function hasType(string $typeName): bool
    {
        return array_key_exists($typeName, $this->typesMap);
    }

    public function getType(string $typeName): ?Type
    {
        return $this->typesMap[$typeName] ?? null;
    }

    /**
     * @param string|Type $type
     */
    public function register($type)
    {
        if (is_string($type) && is_subclass_of($type, Type::class)) {
            /** @var Type $typeInstance */
            $typeInstance = new $type();
            $this->typesMap[$typeInstance->getTypeName()] = $typeInstance;
        } elseif ($type instanceof Type) {
            $this->typesMap[$type->getTypeName()] = $type;
        }
    }

    /**
     * @param TypesProviderInterface[] $providers
     */
    private function initProviders(array $providers)
    {
        foreach ($providers as $provider) {
            if (!is_subclass_of($provider, TypesProviderInterface::class)) {
                continue;
            }

            $types = $provider::getTypes();
            foreach ($types as $type) {
                $this->register($type);
            }
        }
    }
}

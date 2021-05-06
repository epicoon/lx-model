<?php

namespace lx\model\schema;

class ModelAttributeMethod
{
    private string $returnType;
    private array $arguments;

    public function __construct(array $config = [])
    {
        if (array_key_exists('@return', $config)) {
            $this->returnType = $config['@return'];
            unset($config['@return']);
        } else {
            $this->returnType = 'void';
        }

        $this->arguments = [];
        foreach ($config as $name => $type) {
            if (is_array($type)) {
                $default = $type['default'] ?? null;
                $type = $type['type'] ?? 'mixed';
            }

            $def = [
                'name' => $name,
                'type' => $type,
            ];
            if (isset($default)) {
                $def['default'] = $default;
            }

            $this->arguments[] = $def;
        }
    }

    public function getReturn(): string
    {
        return $this->returnType;
    }

    public function getArgumentsAsArray(): array
    {
        return $this->arguments;
    }
}

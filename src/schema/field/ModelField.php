<?php

namespace lx\model\schema\field;

use lx\model\exception\SchemaException;
use lx\model\schema\field\definition\DefinitionFactory;
use lx\model\schema\ModelAttribute;
use lx\model\schema\ModelSchema;
use lx\model\schema\field\definition\AbstractDefinition;
use lx\model\schema\field\type\Type;
use lx\model\schema\field\type\TypesRegistryTrait;
use lx\Service;

class ModelField extends ModelAttribute
{
    use TypesRegistryTrait;

    private ?Type $type;
    private AbstractDefinition $definition;
    private bool $required;
    private bool $readonly;
    /** @var mixed */
    private $default;

    public function __construct(ModelSchema $schema, string $name, array $definition)
    {
        parent::__construct($schema, $name);

        $this->type = $this->getTypeByName($definition['type']);
        if (!$this->type) {
            throw new SchemaException("Type {$definition['type']} not found. Field: {$this->name}.");
        }

        $this->required = $definition['required'] ?? false;
        $this->readonly = $definition['readonly'] ?? false;
        $this->default = $this->type->preprocessDefault($definition['default'] ?? null);
        
        $this->definition = DefinitionFactory::create($this->type->getTypeName());
        $this->definition->init($definition['details'] ?? []);
    }

    public function getService(): Service
    {
        return $this->schema->getService();
    }

    public function getType(): Type
    {
        return $this->type;
    }
    
    public function getDefinition(): AbstractDefinition
    {
        return $this->definition;
    }

    public function getTypeName(): string
    {
        return $this->type->getTypeName();
    }

    public function getPhpType(): string
    {
        return $this->type->getPhpType();
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isReadOnly(): bool
    {
        return $this->readonly;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }
    
    public function getRawValue($value): RawValue
    {
        return new RawValue($value, $this->getDefinition());
    }

    /**
     * @return mixed
     */
    public function getValueIfRequired()
    {
        return $this->type->getValueIfRequired($this->getDefinition());
    }

    /**
     * @return mixed
     */
    public function getPrearrangedValue()
    {
        return $this->type->getPrearrangedValue($this->getDefinition());
    }

    public function isEqual(ModelAttribute $attribute): bool
    {
        if (!($attribute instanceof ModelField)) {
            return false;
        }

        if ($this->getTypeName() != $attribute->getTypeName()) {
            return false;
        }

        return (
            $this->getDefault() === $attribute->getDefault()
            && $this->isRequired() === $attribute->isRequired()
            && $this->definition->isEqual($attribute->definition)
        );
    }

    /**
     * @param mixed $value
     */
    public function validateValue($value): bool
    {
        return $this->type->validateValue($this->getRawValue($value));
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function normalizeValue($value)
    {
        return $this->type->normalizeValue($this->getRawValue($value));
    }
    
    public function valuesAreEqual($value1, $value2): bool
    {
        return $this->type->valuesAreEqual($value1, $value2);
    }

    public function getSetters(): array
    {
        return $this->readonly ? [] : [$this->name => $this];
    }

    public function getGetters(): array
    {
        return [$this->name => $this];
    }

    public function getMethods(): array
    {
        return array_fill_keys($this->type->getMethodNames($this), $this);
    }

    /**
     * @param mixed $currentValue
     * @return mixed
     */
    public function callMethod(string $methodName, $currentValue, array $arguments)
    {
        return $this->type->processMethod($this, $methodName, $currentValue, $arguments);
    }

    public function toArray(): array
    {
        $result = [
            'name' => $this->name,
            'type' => $this->getTypeName(),
            'required' => $this->required,
            'readonly' => $this->readonly,
            'default' => $this->default,
        ];
        $details = $this->definition->toArray();
        if (!empty($details)) {
            $result['details'] = $details;
        }
        return $result;
    }
}

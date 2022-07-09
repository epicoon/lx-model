<?php

namespace lx\model\schema\relation;

use lx\model\schema\ModelAttribute;
use lx\model\schema\ModelSchema;

class ModelRelation extends ModelAttribute
{
    private string $type;
    private string $relModel;
    private ?string $relAttribute;
    private bool $fkHolder;

    public function __construct(ModelSchema $schema, string $name, array $definition)
    {
        parent::__construct($schema, $name);

        $this->type = $definition['type'];
        $this->relModel = $definition['relatedEntityName'];
        $this->relAttribute = $definition['relatedAttributeName'] ?? null;
        $this->fkHolder = $definition['fkHost'] ?? false;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isToOne(): bool
    {
        return ($this->type == RelationTypeEnum::ONE_TO_ONE || $this->type == RelationTypeEnum::MANY_TO_ONE);
    }

    public function isToMany(): bool
    {
        return ($this->type == RelationTypeEnum::ONE_TO_MANY || $this->type == RelationTypeEnum::MANY_TO_MANY);
    }

    public function isOneToOne(): bool
    {
        return  $this->type == RelationTypeEnum::ONE_TO_ONE;
    }

    public function isOneToMany(): bool
    {
        return  $this->type == RelationTypeEnum::ONE_TO_MANY;
    }

    public function isManyToOne(): bool
    {
        return  $this->type == RelationTypeEnum::MANY_TO_ONE;
    }

    public function isManyToMany(): bool
    {
        return  $this->type == RelationTypeEnum::MANY_TO_MANY;
    }

    public function getModelName(): string
    {
        return $this->getSchema()->getModelName();
    }

    public function getRelatedModelName(): string
    {
        return $this->relModel;
    }

    public function getRelatedModelClassName(): string
    {
        return $this->schema->getService()->modelManager->getModelClassName($this->relModel);
    }

    public function getRelatedAttributeName(): ?string
    {
        return  $this->relAttribute;
    }

    public function isUni(): bool
    {
        return ($this->relAttribute === null);
    }

    public function isFkHolder(): bool
    {
        return $this->isManyToOne() || $this->fkHolder;
    }

    public function isEqual(ModelAttribute $attribute): bool
    {
        if (!($attribute instanceof ModelRelation)) {
            return false;
        }

        return ($this->getType() == $attribute->getType()
            && $this->getRelatedModelName() == $attribute->getRelatedModelName()
            && $this->getRelatedAttributeName() == $attribute->getRelatedAttributeName()
            && $this->isFkHolder() == $this->isFkHolder()
        );
    }

    /**
     * @param mixed $currentValue
     * @return mixed
     */
    public function callMethod(string $methodName, $currentValue, array $arguments)
    {

        return null;
    }
}

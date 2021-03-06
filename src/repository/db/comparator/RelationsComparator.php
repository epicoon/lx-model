<?php

namespace lx\model\repository\db\comparator;

use lx\model\schema\ModelAttribute;
use lx\model\schema\ModelSchema;
use lx\model\schema\relation\ModelRelation;

/**
 * Class RelationsComparator
 * @package lx\model\repository\db\comparator
 */
class RelationsComparator extends AttributesComparator
{
    protected function getAttributes(ModelSchema $schema): array
    {
        return $schema->getRelations();
    }

    protected function getAttribute(ModelSchema $schema, string $attributeName): ModelAttribute
    {
        return $schema->getRelation($attributeName);
    }

    protected function processResult(ModelSchema $schemaByCode, array $result): array
    {
        $result['added'] = $this->processGroup($schemaByCode, $result['added']);
        $result['deleted'] = $this->processGroup($schemaByCode, $result['deleted']);
        return $result;
    }

    private function processGroup(ModelSchema $schemaByCode, array $group): array
    {
        $dropList = [];
        foreach ($group as $name) {
            $relation = $schemaByCode->getRelation($name);
            if ($this->canIgnoreRelation($relation)) {
                $dropList[] = $name;
            }
        }

        if (!empty($dropList)) {
            $group = array_values(array_diff($group, $dropList));
        }

        return $group;
    }

    private function canIgnoreRelation(ModelRelation $relation): bool
    {
        if ($relation->isOneToMany()) {
            return true;
        }

        if ($relation->isOneToOne() && !$relation->isFkHolder()) {
            return true;
        }

        if ($relation->isManyToMany()) {
            $modelName = $relation->getModelName();
            $relatedModelName = $relation->getRelatedModelName();
            return ($modelName > $relatedModelName);
        }

        return false;
    }
}

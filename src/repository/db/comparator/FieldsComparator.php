<?php

namespace lx\model\repository\db\comparator;

use lx\model\schema\ModelAttribute;
use lx\model\schema\ModelSchema;

class FieldsComparator extends AttributesComparator
{
    protected function getAttributes(ModelSchema $schema): array
    {
        return $schema->getFields();
    }

    protected function getAttribute(ModelSchema $schema, string $attributeName): ModelAttribute
    {
        return $schema->getField($attributeName);
    }
}

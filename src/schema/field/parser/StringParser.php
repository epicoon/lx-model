<?php

namespace lx\model\schema\field\parser;

use lx\model\schema\field\definition\StringDefinition;

class StringParser extends CommonParser
{
    protected function getDefinitionProtocol(): array
    {
        return array_merge(
            parent::getDefinitionProtocol(),
            [
                ['size', StringDefinition::DEFAULT_LENGTH, null, 'details'],
            ]
        );
    }

    protected function parseType(): void
    {
        preg_match('/^ *\b(.+?)\b(\(\d+?\))?/', $this->definitionSource, $matches);
        $type = $matches[1] ?? null;
        if ($type) {
            $this->definition['type'] = $type;
            $this->definitionSource = preg_replace('/^ *\b(.+?)\b/', '', $this->definitionSource);
        }
        $size = $matches[2] ?? null;
        if ($size) {
            $this->definition['details']['size'] = trim($size, '()');
        }
    }
}

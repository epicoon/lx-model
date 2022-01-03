<?php

namespace lx\model\schema\field\parser;

use lx\model\schema\field\definition\DecimalDefinition;

class DecimalParser extends CommonParser
{
    protected function getDefinitionProtocol(): array
    {
        return array_merge(
            parent::getDefinitionProtocol(),
            [
                ['precision', DecimalDefinition::DEFAULT_PRECISION, null, 'details'],
                ['scale', DecimalDefinition::DEFAULT_SCALE, null, 'details'],
            ]
        );
    }

    protected function parseType(): void
    {
        preg_match('/^ *\b(.+?)\b(\((\d+)\s*,\s*(\d+)\))?/', $this->definitionSource, $matches);
        $type = $matches[1] ?? null;
        if ($type) {
            $this->definition['type'] = $type;
            $this->definitionSource = preg_replace('/^ *\b(.+?)\b/', '', $this->definitionSource);
        }
        $precision = $matches[3] ?? null;
        if ($precision) {
            $this->definition['details']['precision'] = $precision;
        }
        $scale = $matches[4] ?? null;
        if ($scale) {
            $this->definition['details']['scale'] = $scale;
        }
    }
}

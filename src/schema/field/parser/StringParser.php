<?php

namespace lx\model\schema\field\parser;

class StringParser extends CommonParser
{
    protected function getArrayParseProtocol(): array
    {
        return array_merge(
            parent::getArrayParseProtocol(),
            [
                ['size'],
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
            $this->definition['size'] = trim($size, '()');
        }
    }
}

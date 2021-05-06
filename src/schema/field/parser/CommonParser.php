<?php

namespace lx\model\schema\field\parser;

use lx\FlightRecorderHolderTrait;

class CommonParser
{
    use FlightRecorderHolderTrait;

    /** @var string|array */
    protected $definitionSource;
    protected array $definition;

    public function __construct()
    {
        $this->definition = [];
    }

    /**
     * @param string|array $originDefinition
     */
    public function parse($originDefinition): array
    {
        $this->definitionSource = $originDefinition;

        if (is_array($this->definitionSource)) {
            $this->scanArraySource('description');
            if (array_key_exists('definition', $this->definitionSource)) {
                $this->definitionSource = $this->definitionSource['definition'];
            }
        }

        if (is_array($this->definitionSource)) {
            $protocol = $this->getArrayParseProtocol();
            foreach ($protocol as $row) {
                $this->scanArraySource($row[0], $row[1] ?? null, $row[2] ?? null);
            }
        } elseif (is_string($this->definitionSource)) {
            $this->parseStringProcess();
        }

        return $this->definition;
    }

    protected function getArrayParseProtocol(): array
    {
        return [
            ['type'],
            ['default'],
            ['required', false],
            ['readonly', false],
        ];

//            $this->scanArraySource('example');
//            $this->scanArraySource('constraints', null, function($value) {
//                return (array)$value;
//            });
//            $this->scanArraySource('flags');
    }

    protected function parseType(): void
    {
        preg_match('/^ *\b(.+?)\b/', $this->definitionSource, $matches);
        $type = $matches[1] ?? null;
        if ($type) {
            $this->definition['type'] = $type;
            $this->definitionSource = preg_replace('/^ *\b(.+?)\b/', '', $this->definitionSource);
        }
    }

    protected function parseMods(): void
    {
        if (preg_match('/\brequired\b/', $this->definitionSource)) {
            $this->definition['required'] = true;
            $this->definitionSource = preg_replace('/\brequired\b/', '', $this->definitionSource);
        } else {
            $this->definition['required'] = false;
        }

        if (preg_match('/\breadonly\b/', $this->definitionSource)) {
            $this->definition['readonly'] = true;
            $this->definitionSource = preg_replace('/\breadonly\b/', '', $this->definitionSource);
        }

        if (preg_match('/\bdefault\((.+)\)/', $this->definitionSource, $matches)) {
            $default = $matches[1] ?? null;
            if ($default !== null) {
                $this->definition['default'] = $this->stringToValue($default);
                $this->definitionSource = preg_replace('/\bdefault\(.+\)/', '', $this->definitionSource);
            }
        }
    }

    protected function parseString(): void
    {
        // pass
    }

    /**
     * @return bool|float|int|null|string
     */
    protected function stringToValue(string $str)
    {
        if ($str == 'null') {
            return null;
        }
        if ($str == 'false') {
            return false;
        }
        if ($str == 'true') {
            return true;
        }
        if (is_numeric($str)) {
            if (strpos($str, '.') !== false) {
                return (float)$str;
            }
            return (int)$str;
        }
        return trim($str, '"');
    }

    private function parseStringProcess(): void
    {
        $this->parseType();
        $this->parseMods();
        //TODO constraints '/\b[A-Z][\w_\d]*?\(.*?\)/'

        $this->parseString();
    }

    /**
     * @param mixed $default
     * @param callable $callback
     */
    private function scanArraySource(string $key, $default = null, $callback = null)
    {
        if (!array_key_exists($key, $this->definitionSource)) {
            if ($default !== null) {
                $this->definition[$key] = $default;
            }
            return;
        }

        $this->definition[$key] = $callback
            ? $callback($this->definitionSource[$key])
            : $this->definitionSource[$key];
    }
}

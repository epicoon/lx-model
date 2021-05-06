<?php

namespace lx\model\managerTools\refresher\parser;

use lx\FlightRecorderHolderTrait;
use lx\model\managerTools\ModelsContext;
use lx\model\schema\field\parser\CommonParser;
use lx\model\schema\field\type\TypesRegistryTrait;
use lx\Service;

class FieldParser
{
    use FlightRecorderHolderTrait;
    use TypesRegistryTrait;

    private string $fieldName;
    private ModelsContext $context;

    public function __construct(ModelsContext $context, string $fieldName)
    {
        $this->context = $context;
        $this->fieldName = $fieldName;
    }

    public function getService(): Service
    {
        return $this->context->getService();
    }

    /**
     * @param array|string $fieldDefinition
     */
    public function parse($fieldDefinition): ?array
    {
        $typeName = $this->defineType($fieldDefinition);
        if (!$typeName) {
            $this->addFlightRecord('Undefined type');
            return null;
        }

        $type = $this->getTypeByName($typeName);
        $parserClass = $type->getParserClass();
        /** @var CommonParser $parser */
        $parser = new $parserClass();
        $result = $parser->parse($fieldDefinition);

        $recorder = $parser->getFlightRecorder();
        if (!$recorder->isEmpty()) {
            $this->addFlightRecords($recorder->getRecords());
            return null;
        }

        $this->validate();
        if (!$this->getFlightRecorder()->isEmpty()) {
            return null;
        }

        return $result;
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * @param string|array $definition
     */
    private function defineType($definition): ?string
    {
        $types = $this->getTypeNames();

        if (is_array($definition)) {
            if (array_key_exists('type', $definition)) {
                $type = $definition['type'];
                if (in_array($type, $types)) {
                    return $type;
                } else {
                    return null;
                }
            } elseif (array_key_exists('definition', $definition)) {
                $definition = $definition['definition'];
            } else {
                return null;
            }
        }

        if (is_string($definition)) {
            $reg = '/^ *(' . implode('|', $types) . ')/';
            preg_match($reg, $definition, $matches);
            return $matches[1] ?? null;
        }

        return null;
    }

    private function validate(): void
    {
        //TODO
        /*
        Надо доделывать проверки
        Чтобы в бортовой самописец писались проблемы
        Чтобы на уровне SchemaParser тоже был самописец и проблемы отсюда там добавлялись с дополнением имени поля
        Переписывать парсинг строк?
        */
    }
}

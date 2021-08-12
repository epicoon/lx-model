<?php

namespace lx\model\managerTools\refresher\parser;

use lx\FlightRecorderHolderInterface;
use lx\FlightRecorderHolderTrait;
use lx\model\managerTools\ModelsContext;
use lx\model\schema\relation\RelationTypeEnum;
use lx\Service;

class SchemaParser implements FlightRecorderHolderInterface
{
    use FlightRecorderHolderTrait;

    private ModelsContext $context;
    private string $modelName;

    public function __construct(ModelsContext $context)
    {
        $this->context = $context;
    }

    public function getService(): Service
    {
        return $this->context->getService();
    }

    public function parse(array $schema): ?array
    {
        $this->modelName = $schema['name'] ?? '';
        if ($this->modelName == '') {
            $this->addFlightRecord('Schema doesn\'t have a name');
            return null;
        }

        $result = [
            'name' => $this->modelName,
        ];

        $result['fields'] = $this->parseFields($schema);

        $relations = $this->parseRelations($schema);
        if ($relations === null) {
            return null;
        }

        $result['relations'] = $relations;

        return $result;
    }

    private function parseFields(array $schema): array
    {
        $fields = $schema['fields'] ?? null;
        if (!$fields) {
            return [];
        }

        $result = [];
        foreach ($fields as $fieldName => $fieldDefinition) {
            $fieldParser = new FieldParser($this->context, $fieldName);
            $definition = $fieldParser->parse($fieldDefinition);
            if ($definition === null) {
                //TODO warning message
                continue;
            }
            $result[$fieldName] = $definition;
        }

        return $result;
    }

    private function parseRelations(array $schema): ?array
    {
        $relations = $schema['relations'] ?? [];

        $parsedRelations = [];
        foreach ($relations as $relationName => $relationDefinitionString) {
            $relationDefinition = $this->parseRelation($relationName, $relationDefinitionString);
            if ($relationDefinition === null) {
                return null;
            }

            $parsedRelations[$relationName] = $relationDefinition;
        }

        return $parsedRelations;
    }

    /**
     * @param string|array $relation
     */
    private function parseRelation(string $relationName, $relation): ?array
    {
        $relationData = $this->parseRelationDefinition($relation);
        if ($relationData === null) {
            return null;
        }

        /**
         * @var string $type
         * @var bool $fkHost
         * @var string $relEntity
         * @var string|null $relEntityAttribute
         */
        extract($relationData);

        if (!$relEntityAttribute) {
            if ($type == RelationTypeEnum::ONE_TO_MANY || $type == RelationTypeEnum::MANY_TO_MANY) {
                $this->addFlightRecord("Relation '{$relationName}' must have relative entity attribute");
                return null;
            }

            $uni = true;
        } else {
            $uni = false;
        }

        $relNeedToBeFkHost = null;
        if ($type == RelationTypeEnum::ONE_TO_ONE) {
            $relNeedToBeFkHost = !$fkHost;
        }
        if (!$uni
            && !$this->validateContrRelation(
                $relationName,
                $type,
                $relEntity,
                $relEntityAttribute,
                $relNeedToBeFkHost
            )
        ) {
            return null;
        }

        $result = [
            'type' => $type,
            'relatedEntityName' => $relEntity,
            'relatedAttributeName' => $relEntityAttribute,
        ];
        if ($fkHost) {
            $result['fkHost'] = $fkHost;
        }

        return $result;
    }

    private function validateContrRelation(
        string $basicRelationName,
        string $type,
        string $entity,
        string $relationName,
        ?bool $fkHost
    ): bool
    {
        $errString = "Error on relation '$basicRelationName'. ";

        $contrSchema = $this->context->getConductor()->getSchema($entity, true);
        if (!$contrSchema) {
            $this->addFlightRecord(
                $errString
                . "Checking of '$entity::$relationName' existence has failed. Specification can't be loaded"
            );
            return false;
        }

        $relation = $contrSchema['relations'][$relationName] ?? null;
        if (!$relation) {
            $this->addFlightRecord(
                $errString . "Relation is connected to '$entity::$relationName' which has to be defined"
            );
            return false;
        }

        $relType = RelationTypeEnum::getContrType($type);
        if (!$relType) {
            $this->addFlightRecord($errString . "Can't define type for '$entity::$relationName'");
            return false;
        }

        $definition = $this->parseRelationDefinition($relation);
        if ($definition['type'] != $relType) {
            $this->addFlightRecord(
                $errString
                . "For relation type '$type' is expected contr-type '$relType', type '{$definition['type']}' given"
            );
            return false;
        }

        $basicEntity = $this->modelName;
        if ($definition['relEntity'] != $basicEntity) {
            $this->addFlightRecord(
                $errString
                . "Wrong entity definition in '$entity::$relationName'. "
                . "Is expected '$basicEntity', '{$definition['relEntity']}' given"
            );
            return false;
        }

        if ($definition['relEntityAttribute'] != $basicRelationName) {
            $this->addFlightRecord(
                $errString
                . "Wrong relation definition in '$entity::$relationName'. "
                . "Is expected '$basicRelationName', '{$definition['relEntityAttribute']}' given"
            );
            return false;
        }

        if ($fkHost !== null) {
            if ($fkHost && !$definition['fkHost']) {
                $this->addFlightRecord(
                    $errString
                    . "Relations '$basicEntity::$basicRelationName' and  '$entity::$relationName' "
                    . "are without FK-anchor. You have to choose one"
                );
                return false;
            } elseif (!$fkHost && $definition['fkHost']) {
                $this->addFlightRecord(
                    $errString
                    . "Relations '$basicEntity::$basicRelationName' and  '$entity::$relationName' "
                    . "are both have FK-anchor. You have to choose only one"
                );
                return false;
            }
        }

        return true;
    }

    private function parseRelationDefinition(string $definition): ?array
    {
        $definitionArray = preg_split('/ +/',$definition);

        $type = $definitionArray[0] ?? '';
        $fkHost = false;
        if (preg_match('/fk\)?$/', $type)) {
            $fkHost = true;
            $type = preg_replace('/fk\)?$/', '', $type);
        }
        $type = trim($type, ')(');
        switch ($type) {
            case '--':
                $type = RelationTypeEnum::ONE_TO_ONE;
                break;
            case '-<':
                $type = RelationTypeEnum::ONE_TO_MANY;
                break;
            case '>-':
                $type = RelationTypeEnum::MANY_TO_ONE;
                break;
            case '><':
                $type = RelationTypeEnum::MANY_TO_MANY;
                break;
            default:
                $type = false;
        }

        if ($type === false) {
            $this->addFlightRecord('Unknown relation type syntax');
            return null;
        }

        $relEntityArray = explode('.', ($definitionArray[1] ?? ''));
        $relEntity = $relEntityArray[0];
        $relEntityAttribute = $relEntityArray[1] ?? null;

        return [
            'type' => $type,
            'fkHost' => $fkHost,
            'relEntity' => $relEntity,
            'relEntityAttribute' => $relEntityAttribute,
        ];
    }
}

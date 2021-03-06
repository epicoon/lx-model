<?php

namespace lx\model\repository\db\comparator;

use lx\model\schema\ModelAttribute;
use lx\model\schema\ModelSchema;

/**
 * Class AttributesComparator
 * @package lx\model\repository\db\comparator
 */
abstract class AttributesComparator
{
    /**
     * @param ModelSchema $schemaByCode
     * @param ModelSchema $schemaByRepo
     * @return array [changed[], renamed[], added[], deleted[]]
     */
    public function run(ModelSchema $schemaByCode, ModelSchema $schemaByRepo): array
    {
        /**
         * @var array $namesCodeOnly
         * @var array $namesRepoOnly
         * @var array $namesCommon
         */
        extract($this->splitAttributeNames($schemaByCode, $schemaByRepo));

        $changed = $this->defineChangedAttributes($schemaByCode, $schemaByRepo, $namesCommon);
        $renamed = $this->defineRenamedAttributes($schemaByCode, $schemaByRepo, $namesCodeOnly, $namesRepoOnly);
        foreach ($renamed as $pare) {
            unset($namesCodeOnly[array_search($pare['new'], $namesCodeOnly)]);
            unset($namesRepoOnly[array_search($pare['old'], $namesRepoOnly)]);
        }

        return $this->processResult($schemaByCode, [
            'changed' => $changed,
            'renamed' => $renamed,
            'added' => $namesCodeOnly,
            'deleted' => $namesRepoOnly,
        ]);
    }

    abstract protected function getAttributes(ModelSchema $schema): array;

    abstract protected function getAttribute(ModelSchema $schema, string $attributeName): ModelAttribute;

    protected function processResult(ModelSchema $schemaByCode, array $result): array
    {
        return $result;
    }

    /**
     * @param ModelSchema $schemaByCode
     * @param ModelSchema $schemaByRepo
     * @return array [namesCodeOnly[], namesRepoOnly[], namesCommon[]]
     */
    private function splitAttributeNames(ModelSchema $schemaByCode, ModelSchema $schemaByRepo): array
    {
        $codeNames = array_keys($this->getAttributes($schemaByCode));
        $repoNames = array_keys($this->getAttributes($schemaByRepo));

        $namesRepoOnly = array_diff($repoNames, $codeNames);
        $namesCodeOnly = array_diff($codeNames, $repoNames);
        $namesCommon = array_diff($repoNames, $namesRepoOnly);

        return [
            'namesCodeOnly' => $namesCodeOnly,
            'namesRepoOnly' => $namesRepoOnly,
            'namesCommon' => $namesCommon,
        ];
    }

    private function defineChangedAttributes(
        ModelSchema $schemaByCode,
        ModelSchema $schemaByRepo,
        array $namesCommon
    ): array
    {
        $result = [];
        foreach ($namesCommon as $name) {
            $codeAttribute = $this->getAttribute($schemaByCode, $name);
            $repoAttribute = $this->getAttribute($schemaByRepo, $name);

            if (!$codeAttribute->isEqual($repoAttribute)) {
                $result[] = $name;
            }
        }

        return $result;
    }

    private function defineRenamedAttributes(
        ModelSchema $schemaByCode,
        ModelSchema $schemaByRepo,
        array $namesCodeOnly,
        array $namesRepoOnly
    ): array
    {
        $result = [];
        foreach ($namesCodeOnly as $nameInCode) {
            foreach ($namesRepoOnly as $nameInRepo) {
                $codeAttribute = $this->getAttribute($schemaByCode, $nameInCode);
                $repoAttribute = $this->getAttribute($schemaByRepo, $nameInRepo);
                if ($codeAttribute->isEqual($repoAttribute)) {
                    $result[] = [
                        'old' => $nameInRepo,
                        'new' => $nameInCode,
                    ];
                    break;
                }
            }
        }

        return $result;
    }
}

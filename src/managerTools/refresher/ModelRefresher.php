<?php

namespace lx\model\managerTools\refresher;

use lx\CodeConverterHelper;
use lx\model\modelTools\RelatedModelsCollection;
use lx\model\repository\db\tools\SchemaBuffer;
use lx\model\managerTools\ModelsContext;
use lx\model\managerTools\refresher\parser\SchemaParser;
use lx\model\schema\ModelSchema;
use lx\model\schema\field\ModelField;

class ModelRefresher
{
    private ModelsContext $context;
    private ModelNamesProvider $modelNamesProvider;
    private string $fullModelName;
    private string $modelName;
    private string $modelNamespace;
    private string $modelPath;
    private array $schemaArray;
    private ModelSchema $schema;
    private string $mediatorName;
    private string $mediatorNamespace;
    private string $mediatorPath;
    private array $options;
    private array $uses;
    private RefreshReport $report;

    public function __construct(ModelsContext $context, string $modelName, ModelNamesProvider $modelNamesProvider)
    {
        $this->context = $context;
        $this->modelNamesProvider = $modelNamesProvider;

        $this->fullModelName = $modelName;
        $this->defineNames();
        $this->modelPath = str_replace('\\', '/', $modelName) . '.php';
        $this->mediatorPath = str_replace('\\', '/', $modelName) . 'Mediator.php';

        $this->schemaArray = [];
        $this->options = [];
        $this->uses = [];
        $this->report = new RefreshReport();
    }

    public function run(array $options): RefreshReport
    {
        $this->options = $options;

        if (!$this->needUpdate()) {
            return $this->report;
        }

        $this->report->addToModelsNeedUpdate($this->fullModelName);

        if ($this->getOption(ModelsRefresher::OPTION_PREVIEW)) {
            return $this->report;
        }

        $schema = $this->context->getConductor()->getSchema($this->fullModelName, true);
        $parser = new SchemaParser($this->context);
        $parseResult = $parser->parse($schema);
        if ($parseResult === null) {
            $this->report->addListToErrors($parser->getFlightRecorder()->getRecords());
            return $this->report;
        }

        $this->schemaArray = $parseResult;
        $this->schema = ModelSchema::createFromArray($this->schemaArray, $this->context->getService());

        $this->inspectMediatorFile();
        $this->inspectModelFile();

        return $this->report;
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function inspectMediatorFile(): void
    {
        $conductor = $this->context->getConductor();

        $file = $conductor->getModelMediatorFile($this->fullModelName);
        if (!$file) {
            $dir = $conductor->getModelMediatorsDirectory();
            if (!$dir->exists()) {
                $dir->make();
            }

            $file = $dir->makeFile($this->mediatorPath);
        }

        $template = $conductor->getMediatorTemplate();
        $text = $template;
        $text = str_replace('<namespace>', $this->mediatorNamespace, $text);
        $propertiesString = $this->getPropertiesString();
        $text = str_replace('<use>', $this->getUseString(), $text);
        $text = str_replace('<class>', $this->mediatorName, $text);
        $text = str_replace('<service>', $this->context->getService()->name, $text);
        $schemaString = $this->getSchemaString();
        $text = str_replace('<schema>', $schemaString, $text);
        $text = str_replace('<fields>', $propertiesString, $text);

        $modelSchema = ModelSchema::createFromArray($this->schemaArray, $this->context->getService());
        SchemaBuffer::setModelSchema($this->fullModelName, $modelSchema);
        if ($file->exists()) {
            $this->report->addToMediatorUpdated($this->fullModelName, $this->mediatorName);
        } else {
            $this->report->addToMediatorCreated($this->fullModelName, $this->mediatorName);
        }

        $file->put($text);
    }

    private function inspectModelFile(): void
    {
        $conductor = $this->context->getConductor();

        $file = $conductor->getModelFile($this->fullModelName);
        if ($file) {
            return;
        }

        $dir = $conductor->getModelsDirectory();
        if (!$dir->exists()) {
            $dir->make();
        }

        $file = $dir->makeFile($this->modelPath);

        $template = $conductor->getModelTemplate();
        $text = $template;
        $text = str_replace('<namespace>', $this->modelNamespace, $text);
        $text = str_replace('<class>', $this->modelName, $text);
        $text = str_replace('<mediatorClass>', $this->mediatorNamespace . '\\' . $this->mediatorName, $text);
        $text = str_replace('<mediator>', $this->mediatorName, $text);

        $file->put($text);
        $this->report->addToModelsCreated(
            $this->fullModelName,
            $this->modelNamespace . '\\' . $this->modelName
        );
    }

    private function getOption(string $name): bool
    {
        return in_array($name, $this->options);
    }
    
    private function addUsed(string $name): void
    {
        if (!in_array($name, $this->uses)) {
            $this->uses[] = "use $name;";
        }
    }

    private function getUseString(): string
    {
        $uses = $this->uses;
        foreach ($this->schema->getRelations() as $relation) {
            if ($relation->isToMany()) {
                $class = RelatedModelsCollection::class;
                $class = "use {$class};";
                if (!in_array($class, $uses)) {
                    $uses[] = $class;
                }
           }

            $relModelName = $relation->getRelatedModelName();
            $relModelClass = $this->modelNamesProvider->getModelNamespace($relModelName)
                . '\\' . $this->modelNamesProvider->getShortModelName($relModelName);
            if (!in_array($relModelClass, $uses)) {
                $uses[] = "use {$relModelClass};";
            }
        }

        if (empty($uses)) {
            return '';
        }

        return PHP_EOL . implode(PHP_EOL, $uses);
    }

    private function getSchemaString(): string
    {
        return CodeConverterHelper::arrayToPhpCode($this->schemaArray, 2);
    }

    private function getPropertiesString(): string
    {
        $setters = $this->schema->getSetters();
        $getters = array_diff_key($this->schema->getGetters(), $setters);

        $settersArr = [];
        /** @var ModelField $setter */
        foreach ($setters as $name => $setter) {
            $phpType = $setter->getPhpType();
            if (preg_match('/\\\\/', $phpType)) {
                $this->addUsed($phpType);
                $arr = explode('\\', $phpType);
                $phpType = array_pop($arr);
            }
            $settersArr[] = ' * @property ' . $phpType . ' $' . $name;
        }

        $gettersArr = [];
        /** @var ModelField $getter */
        foreach ($getters as $name => $getter) {
            $phpType = $getter->getPhpType();
            if (preg_match('/\\\\/', $phpType)) {
                $this->addUsed($phpType);
                $arr = explode('\\', $phpType);
                $phpType = array_pop($arr);
            }
            $gettersArr[] = ' * @property-read ' . $phpType . ' $' . $name;
        }

        $methods = $this->schema->getMethods();
        $methodsArr = [];
        /** @var ModelField $field */
        foreach ($methods as $methodName => $field) {
            $method = $field->getType()->getMethodDefinition($methodName);
            $returnType = $method->getReturn();
            $args = $method->getArgumentsAsArray();
            foreach ($args as &$arg) {
                $str = $arg['type'] . ' $' . $arg['name'];
                if (array_key_exists('default', $arg)) {
                    $str .= ' = ' . $arg['default'];
                }
                $arg = $str;
            }
            unset($arg);
            $methodsArr[] = " * @method $returnType $methodName(" . implode(', ', $args) . ')';
        }

        foreach ($this->schema->getRelations() as $relation) {
            $relModelType = $this->modelNamesProvider->getShortModelName($relation->getRelatedModelName());
            $relationName = $relation->getName();
            if ($relation->isToMany()) {
                $settersArr[] = " * @property RelatedModelsCollection&{$relModelType}[] \${$relationName}";
            } else {
                $settersArr[] = " * @property {$relModelType} \${$relationName}";
            }

        }

        $arr = [];
        if (!empty($settersArr)) {
            $arr[] = ' *';
            $arr = array_merge($arr, $settersArr);
        }
        if (!empty($gettersArr)) {
            $arr[] = ' *';
            $arr = array_merge($arr, $gettersArr);
        }
        if (!empty($methodsArr)) {
            $arr[] = ' *';
            $arr = array_merge($arr, $methodsArr);
        }

        $result = implode(PHP_EOL, $arr);
        if ($result != '') {
            $result = PHP_EOL . $result;
        }

        return $result;
    }

    private function needUpdate(): bool
    {
        $conductor = $this->context->getConductor();

        $modelFile = $conductor->getModelFile($this->fullModelName);
        if (!$modelFile) {
            return true;
        }

        $modelMediatorFile = $conductor->getModelMediatorFile($this->fullModelName);
        if (!$modelMediatorFile) {
            return true;
        }

        $modelSchemaFile = $conductor->getModelSchemaFile($this->fullModelName);
        return $modelSchemaFile->isNewer($modelMediatorFile);
    }

    private function defineNames(): void
    {
        $this->modelName = $this->modelNamesProvider->getShortModelName($this->fullModelName);
        $this->modelNamespace = $this->modelNamesProvider->getModelNamespace($this->fullModelName);
        $this->mediatorName = $this->modelNamesProvider->getMediatorName($this->fullModelName);
        $this->mediatorNamespace = $this->modelNamesProvider->getMediatorNamespace($this->fullModelName);
    }
}

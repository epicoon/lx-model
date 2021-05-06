<?php

namespace lx\model\managerTools;

use Exception;
use lx;
use lx\DataFileInterface;
use lx\Directory;
use lx\File;

class Conductor
{
    const MODEL_CLASSES_MAP_FILE = 'map.json';

    private ModelsContext $context;
    private array $schemas;
    /** @var array<DataFileInterface> */
    private array $modelSchemaFilesMap;

    public function __construct(ModelsContext $context)
    {
        $this->context = $context;
        $this->schemas = [];
        $this->modelSchemaFilesMap = [];
    }

    public function getAllModelNames(): array
    {
        $schemas = $this->getSchemas();
        return array_keys($schemas);
    }

    public function validateModelNames(array $modelNames): array
    {
        $realNames = $this->getAllModelNames();
        return array_values(array_intersect($realNames, $modelNames));
    }

    public function getSchemas(): array
    {
        if (empty($this->schemas)) {
            $this->loadSchemas();
        }

        return $this->schemas;
    }

    public function getSchema(string $modelName, bool $reload = false): ?array
    {
        $this->getSchemas();

        if ($reload) {
            $file = $this->modelSchemaFilesMap[$modelName] ?? null;
            if (!$file) {
                return null;
            }

            $data = $file->get();
            if (array_key_exists('models', $data)) {
                $this->schemas[$modelName] = $data['models'][$modelName];
            } else {
                $this->schemas[$modelName] = $data;
            }
        }

        return $this->schemas[$modelName] ?? null;
    }

    public function getModelClassesMapFile(): ?File
    {
        try {
            /** @var File|null $result */
            $result = $this->getModelMediatorsDirectory()->get(self::MODEL_CLASSES_MAP_FILE);
            return $result;
        } catch (Exception $exception) {
            return null;
        }
    }

    public function getModelSchemasDirectory(): Directory
    {
        /** @var Directory $dir */
        $dir = $this->context->getService()->getFile(
            $this->context->getModelSchemasPath(),
            Directory::class
        );
        return $dir;
    }

    public function getModelSchemaFile(string $modelName): ?DataFileInterface
    {
        $this->loadSchemas();
        return $this->modelSchemaFilesMap[$modelName] ?? null;
    }

    public function getMigrationsDirectory(): Directory
    {
        return new Directory($this->context->getService()->conductor->getSystemPath('migrations'));
    }

    public function getModelMediatorsDirectory(): Directory
    {
        $psr = $this->context->getService()->getConfig('autoload.psr-4');
        if (!$psr) {
            throw new Exception('Models need PSR-4 autoload rules');
        }

        $sysPath = null;
        foreach ($psr as $namespace => $paths) {
            foreach ((array)$paths as $path) {
                if ($path == '') {
                    continue;
                }

                if (strpos('.system', $path) === 0) {
                    $sysPath = $path;
                    break 2;
                }
            }
        }
        if (!$sysPath) {
            throw new Exception('Models need PSR-4 autoload rules for ".system" classes');
        }

        /** @var Directory $dir */
        $dir = $this->context->getService()->getFile(
            $sysPath . '/' . $this->context->getModelsPath(),
            Directory::class
        );
        return $dir;
    }

    public function getModelMediatorFile(string $modelName): ?File
    {
        $relPath = str_replace('\\', '/', $modelName) . 'Mediator.php';
        $dir = $this->getModelMediatorsDirectory();
        if (!$dir->exists()) {
            return null;
        }

        /** @var File|null $file */
        $file = $dir->get($relPath);
        return $file;
    }

    public function getModelsDirectory(): Directory
    {
        /** @var Directory $dir */
        $dir = $this->context->getService()->getFile(
            $this->context->getModelsPath(),
            Directory::class
        );
        return $dir;
    }

    public function getModelFile(string $modelName): ?File
    {
        $relPath = str_replace('\\', '/', $modelName) . '.php';
        $dir = $this->getModelsDirectory();
        if (!$dir->exists()) {
            return null;
        }

        /** @var File|null $file */
        $file = $dir->get($relPath);
        return $file;
    }

    public function getMediatorTemplate(): string
    {
        return file_get_contents(__DIR__ . '/refresher/tpl/mediator');
    }

    public function getModelTemplate(): string
    {
        return file_get_contents(__DIR__ . '/refresher/tpl/model');
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function loadSchemas(): void
    {
        if (!empty($this->schemas)) {
            return;
        }

        $modelSchemasDir = $this->getModelSchemasDirectory();
        if (!$modelSchemasDir->exists()) {
            return;
        }

        $files = $modelSchemasDir->getAllFiles(
            '*.' . $this->context->getModelSchemasExtension(),
            ['fileClass' => DataFileInterface::class]
        );

        $schemas = [];
        foreach ($files as $file) {
            $data = $file->get();
            if (array_key_exists('models', $data)) {
                foreach ($data['models'] as $modelData) {
                    if (array_key_exists('name', $modelData)) {
                        $name = $modelData['name'];
                        $schemas[$name] = $modelData;
                        $this->modelSchemaFilesMap[$name] = $file;
                    }
                }
            } elseif (array_key_exists('name', $data)) {
                $name = $data['name'];
                $schemas[$name] = $data;
                $this->modelSchemaFilesMap[$name] = $file;
            }
        }

        $this->schemas = $schemas;
    }
}

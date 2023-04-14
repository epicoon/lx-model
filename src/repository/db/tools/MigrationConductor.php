<?php

namespace lx\model\repository\db\tools;

use lx;
use lx\ArrayHelper;
use lx\DataFileInterface;
use lx\File;

class MigrationConductor
{
    private RepositoryContext $context;
    private array $appliedList;

    public function __construct(RepositoryContext $context)
    {
        $this->context = $context;
        $this->appliedList = [];
    }

    /**
     * @return array<Migration>
     */
    public function getMigrations(): array
    {
        $appliedList = $this->getAppliedList();

        $migrationsDir = $this->context->getConductor()->getMigrationsDirectory();
        $migrationsList = $migrationsDir->getFiles([
            'fileClass' => DataFileInterface::class,
            'sort' => SCANDIR_SORT_ASCENDING
        ]);

        $result = [];
        /** @var File&DataFileInterface $file */
        foreach ($migrationsList as $file) {
            $result[] = new Migration($file, in_array($file->getCleanName(), $appliedList));
        }

        return $result;
    }

    public function getMigration(string $name): ?Migration
    {
        $migrationsDir = $this->context->getConductor()->getMigrationsDirectory();

        if (!$migrationsDir->contains($name)) {
            return null;
        }

        /** @var File $file */
        $file = lx::$app->diProcessor->createByInterface(DataFileInterface::class, [
            $migrationsDir->getPath() . '/' . $name,
        ]);

        $appliedList = $this->getAppliedList();
        return new Migration($file, in_array($file->getCleanName(), $appliedList));
    }

    public function getAppliedList(): array
    {
        if (empty($this->appliedList)) {
            $sysTableProvider = new SysTablesProvider($this->context);
            if ($sysTableProvider->isTableExist(SysTablesProvider::MIGRATIONS_TABLE)) {
                $list = $sysTableProvider
                    ->getTable(SysTablesProvider::MIGRATIONS_TABLE)
                    ->select('version', ['service' => $this->context->getService()->name]);
                $this->appliedList = ArrayHelper::getColumn($list, 'version');
                usort($this->appliedList, function ($a, $b) {
                    if ($a > $b) return -1;
                    if ($b > $a) return 1;
                    return 0;
                });
            } else {
                $this->appliedList = [];
            }
        }

        return $this->appliedList;
    }

    public function getUnappliedList(): array
    {
        $migrations = $this->getMigrations();
        $result = [];
        foreach ($migrations as $migration) {
            if (!$migration->isApplied()) {
                $result[] = $migration->getVersion();
            }
        }

        return $result;
    }

    public function actualize(array $unapplied, array $applied): bool
    {
        $sysTableProvider = new SysTablesProvider($this->context);
        $table = $sysTableProvider->getTable(SysTablesProvider::MIGRATIONS_TABLE);

        if (!empty($unapplied)) {
            $table->delete([
                'version' => $unapplied,
            ]);
        }

        if (!empty($applied)) {
            $fields = [];
            foreach ($applied as $appliedName) {
                $fields[] = [$this->context->getService()->name, $appliedName, date('Y-m-d h:i:s')];
            }

            $table->insert(['service', 'version', 'created_at'], $fields);
        }

        return true;
    }

    public function hasUnapplied(): bool
    {
        $migrations = $this->getMigrations();
        foreach ($migrations as $migration) {
            if (!$migration->isApplied()) {
                return true;
            }
        }

        return false;
    }
}

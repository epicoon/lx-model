<?php

namespace lx\model\repository\db\tools;

use lx\DataFileInterface;
use lx\File;
use lx\model\repository\MigrationInterface;

class Migration implements MigrationInterface
{
    /** @var DataFileInterface&File */
    private DataFileInterface $file;
    private bool $isApplied;

    public function __construct(DataFileInterface $file, bool $isApplied)
    {
        $this->file = $file;
        $this->isApplied = $isApplied;
    }

    public function isApplied(): bool
    {
        return $this->isApplied;
    }

    public function getName(): string
    {
        return $this->file->getName();
    }

    public function getVersion(): string
    {
        return $this->file->getCleanName();
    }

    public function getFile(): DataFileInterface
    {
        return $this->file;
    }

    public function get(): array
    {
        return $this->file->get();
    }
}

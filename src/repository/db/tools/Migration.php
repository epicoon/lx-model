<?php

namespace lx\model\repository\db\tools;

use lx\DataFileInterface;
use lx\File;
use lx\model\repository\MigrationInterface;

/**
 * Class Migration
 * @package lx\model\repository\db\tools
 */
class Migration implements MigrationInterface
{
    /** @var File&DataFileInterface */
    private $file;
    private bool $isApplied;

    /**
     * Migration constructor.
     * @param $file
     * @param bool $isApplied
     */
    public function __construct($file, bool $isApplied)
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

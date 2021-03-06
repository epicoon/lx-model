<?php

namespace lx\model\repository;

use lx\DataFileInterface;

/**
 * Interface MigrationInterface
 * @package lx\model\repository
 */
interface MigrationInterface
{
    public function isApplied(): bool;
    public function getName(): string;
    public function getFile(): DataFileInterface;
    public function get(): array;
}

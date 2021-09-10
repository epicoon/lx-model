<?php

namespace lx\model\repository;

use lx\DataFileInterface;

interface MigrationInterface
{
    public function isApplied(): bool;
    public function getName(): string;
    public function getFile(): DataFileInterface;
    public function get(): array;
}

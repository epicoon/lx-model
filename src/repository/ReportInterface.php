<?php

namespace lx\model\repository;

/**
 * Interface ReportInterface
 * @package lx\model\repository
 */
interface ReportInterface
{
    public function toArray(): array;

    public function isEmpty(): bool;
}

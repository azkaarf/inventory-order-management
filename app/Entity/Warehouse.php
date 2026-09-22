<?php

namespace App\Entity;

final class Warehouse
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $location,
        public readonly bool $isActive,
    ) {
    }
}

<?php

namespace App\Entity;

final class Category
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $description,
    ) {
    }
}

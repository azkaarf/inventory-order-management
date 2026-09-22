<?php

namespace App\Entity;

final class Customer
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $contact,
        public readonly ?string $address,
        public readonly bool $isActive,
    ) {
    }
}

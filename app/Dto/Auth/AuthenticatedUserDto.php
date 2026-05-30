<?php

namespace App\Dto\Auth;

use Spatie\LaravelData\Data;

class AuthenticatedUserDto extends Data
{
    public function __construct(
        public int|string $id,
        public string $name,
        public string $email,
        public string $role,
    ) {}
}

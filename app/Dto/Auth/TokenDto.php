<?php

namespace App\Dto\Auth;

use Spatie\LaravelData\Data;

class TokenDto extends Data
{
    public function __construct(
        public string $token,
        public string $tokenType,
        public AuthenticatedUserDto $user,
    ) {}
}

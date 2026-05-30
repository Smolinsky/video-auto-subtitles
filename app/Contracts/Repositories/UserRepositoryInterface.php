<?php

namespace App\Contracts\Repositories;

use App\Dto\Auth\CreateUserRecordDto;
use App\Models\User;

interface UserRepositoryInterface
{
    public function create(CreateUserRecordDto $dto): User;

    public function findByEmail(string $email): ?User;
}

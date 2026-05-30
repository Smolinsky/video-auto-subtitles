<?php

namespace App\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Dto\Auth\CreateUserRecordDto;
use App\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function create(CreateUserRecordDto $dto): User
    {
        return User::query()->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $dto->password,
        ])->refresh();
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }
}

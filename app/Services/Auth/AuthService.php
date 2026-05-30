<?php

namespace App\Services\Auth;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Dto\Auth\CreateUserRecordDto;
use App\Dto\Auth\LoginUserDto;
use App\Dto\Auth\RegisterUserDto;
use App\Dto\Auth\TokenDto;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

readonly class AuthService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function register(RegisterUserDto $dto): TokenDto
    {
        $user = $this->userRepository->create(new CreateUserRecordDto(
            name: $dto->name,
            email: $dto->email,
            password: $dto->password,
        ));

        return $this->issueToken($user);
    }

    public function login(LoginUserDto $dto): TokenDto
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are invalid.'],
            ]);
        }

        return $this->issueToken($user);
    }

    public function logout(User $user): void
    {
        $user->token()?->revoke();
    }

    private function issueToken(User $user): TokenDto
    {
        $token = $user->createToken('api-token')->accessToken;

        return TokenDto::from([
            'token' => $token,
            'tokenType' => 'Bearer',
            'user' => $user->toArray(),
        ]);
    }
}

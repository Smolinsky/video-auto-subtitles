<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('passport:client', [
            '--personal' => true,
            '--name' => 'Test Personal Access Client',
            '--provider' => 'users',
        ]);
    }

    public function test_user_can_register_and_receive_bearer_token(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'passwordConfirmation' => 'secret123',
        ])->assertCreated()
            ->assertJsonPath('data.tokenType', 'Bearer')
            ->assertJsonPath('data.user.email', 'jane@example.com')
            ->assertJsonStructure([
                'data' => ['token', 'tokenType', 'user'],
            ]);
    }

    public function test_user_can_login_and_receive_bearer_token(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'passwordConfirmation' => 'secret123',
        ])->assertCreated();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'secret123',
        ])->assertOk()
            ->assertJsonPath('data.tokenType', 'Bearer')
            ->assertJsonPath('data.user.email', 'john@example.com');
    }

    public function test_authenticated_user_can_logout(): void
    {
        $token = $this->postJson('/api/v1/auth/register', [
            'name' => 'Alex Doe',
            'email' => 'alex@example.com',
            'password' => 'secret123',
            'passwordConfirmation' => 'secret123',
        ])->assertCreated()->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Token revoked successfully.');
    }
}

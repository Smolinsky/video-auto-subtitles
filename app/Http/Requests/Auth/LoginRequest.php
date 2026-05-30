<?php

namespace App\Http\Requests\Auth;

use App\Dto\Auth\LoginUserDto;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function getDto(): LoginUserDto
    {
        return new LoginUserDto(
            email: $this->string('email')->lower()->toString(),
            password: $this->string('password')->toString(),
        );
    }
}

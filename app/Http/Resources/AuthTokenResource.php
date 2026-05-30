<?php

namespace App\Http\Resources;

use App\Dto\Auth\TokenDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TokenDto */
class AuthTokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->token,
            'tokenType' => $this->tokenType,
            'user' => new AuthenticatedUserResource($this->user),
        ];
    }
}

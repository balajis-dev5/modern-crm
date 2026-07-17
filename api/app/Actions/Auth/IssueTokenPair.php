<?php

namespace App\Actions\Auth;

use App\Models\RefreshToken;
use App\Models\User;
use App\Support\Jwt;
use Illuminate\Support\Str;

class IssueTokenPair
{
    /**
     * @return array{access_token:string,refresh_token:string,expires_in:int}
     */
    public function handle(User $user, ?string $family = null): array
    {
        $plain = Str::random(64);

        RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'family' => $family ?? (string) Str::uuid(),
            'expires_at' => now()->addSeconds((int) config('jwt.refresh_ttl')),
        ]);

        return [
            'access_token' => Jwt::issue($user->id),
            'refresh_token' => $plain,
            'expires_in' => (int) config('jwt.access_ttl'),
        ];
    }
}

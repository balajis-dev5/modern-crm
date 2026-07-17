<?php

namespace App\Actions\Auth;

use App\Models\RefreshToken;
use App\Models\User;

/** Logout-everywhere: kills every active refresh token the user holds. */
class RevokeUserTokens
{
    public function handle(User $user): void
    {
        RefreshToken::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}

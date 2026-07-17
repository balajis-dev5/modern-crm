<?php

namespace App\Actions\Auth;

use App\Models\RefreshToken;

class RotateRefreshToken
{
    public function __construct(private IssueTokenPair $issueTokenPair) {}

    /**
     * Exchange a refresh token for a new pair.
     *
     * Reuse detection: presenting an already-revoked token means the token
     * leaked (the legitimate client holds a newer one), so the entire family
     * is revoked and every session started from that login dies.
     *
     * @return array{access_token:string,refresh_token:string,expires_in:int}|null
     */
    public function handle(string $plainToken): ?array
    {
        $row = RefreshToken::where('token_hash', hash('sha256', $plainToken))->first();

        if (! $row) {
            return null;
        }

        if (! $row->isActive()) {
            RefreshToken::where('family', $row->family)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            return null;
        }

        $row->update(['revoked_at' => now()]);

        return $this->issueTokenPair->handle($row->user, $row->family);
    }
}

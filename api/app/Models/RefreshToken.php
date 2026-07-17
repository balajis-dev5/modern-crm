<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per issued refresh token. Tokens belong to a "family" that starts
 * at login; rotation adds a new row and revokes the old one. If a revoked
 * token is ever presented again (theft replay), the whole family dies.
 */
class RefreshToken extends Model
{
    protected $fillable = ['user_id', 'token_hash', 'family', 'expires_at', 'revoked_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Minimal HS256 JWT — encode/verify only, no external package.
 * ~60 lines is cheaper to audit than a dependency, and access tokens
 * here carry exactly three claims.
 */
class Jwt
{
    public static function issue(int $userId, ?int $ttlSeconds = null): string
    {
        $ttl = $ttlSeconds ?? (int) config('jwt.access_ttl');

        $header = self::b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = self::b64(json_encode([
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + $ttl,
            'jti' => (string) Str::uuid(),
        ]));

        return "{$header}.{$payload}.".self::sign("{$header}.{$payload}");
    }

    /** @return array{sub:int,iat:int,exp:int,jti:string}|null null = invalid or expired */
    public static function verify(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        if (! hash_equals(self::sign("{$header}.{$payload}"), $signature)) {
            return null;
        }

        $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        if (! is_array($claims) || ! isset($claims['sub'], $claims['exp'])) {
            return null;
        }

        if ($claims['exp'] < time()) {
            return null;
        }

        return $claims;
    }

    private static function sign(string $data): string
    {
        return self::b64(hash_hmac('sha256', $data, config('jwt.secret'), true));
    }

    private static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

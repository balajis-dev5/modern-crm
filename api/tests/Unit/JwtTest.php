<?php

namespace Tests\Unit;

use App\Support\Jwt;
use Tests\TestCase;

class JwtTest extends TestCase
{
    public function test_issue_and_verify_roundtrip(): void
    {
        $claims = Jwt::verify(Jwt::issue(42));

        $this->assertSame(42, $claims['sub']);
        $this->assertGreaterThan(time(), $claims['exp']);
    }

    public function test_tampered_payload_is_rejected(): void
    {
        [$header, $payload, $sig] = explode('.', Jwt::issue(42));

        $forged = rtrim(strtr(base64_encode(json_encode([
            'sub' => 999, 'iat' => time(), 'exp' => time() + 900, 'jti' => 'x',
        ])), '+/', '-_'), '=');

        $this->assertNull(Jwt::verify("{$header}.{$forged}.{$sig}"));
    }

    public function test_expired_token_is_rejected(): void
    {
        $this->assertNull(Jwt::verify(Jwt::issue(42, ttlSeconds: -10)));
    }

    public function test_garbage_is_rejected(): void
    {
        $this->assertNull(Jwt::verify('not-a-token'));
        $this->assertNull(Jwt::verify('a.b'));
        $this->assertNull(Jwt::verify(''));
    }
}

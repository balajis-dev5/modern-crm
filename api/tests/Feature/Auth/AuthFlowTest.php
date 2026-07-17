<?php

namespace Tests\Feature\Auth;

use App\Models\RefreshToken;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'member', 'label' => 'Member']);
    }

    public function test_register_returns_user_and_token_pair(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Asha K',
            'email' => 'asha@example.com',
            'password' => 'super-secret-1',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'access_token', 'refresh_token', 'expires_in']);

        $this->assertDatabaseHas('users', ['email' => 'asha@example.com']);
        $this->assertDatabaseCount('refresh_tokens', 1);
    }

    public function test_register_validates_input_with_error_envelope(): void
    {
        $response = $this->postJson('/api/v1/auth/register', ['email' => 'not-an-email']);

        $response->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['message', 'errors' => ['name', 'email', 'password']]);
    }

    public function test_login_rejects_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'right-password']);

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'INVALID_CREDENTIALS');
    }

    public function test_login_is_rate_limited_per_email(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'victim@example.com', 'password' => 'nope']);
        }

        $this->postJson('/api/v1/auth/login', ['email' => 'victim@example.com', 'password' => 'nope'])
            ->assertStatus(429)
            ->assertJsonPath('code', 'RATE_LIMITED');
    }

    public function test_protected_route_requires_valid_token(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized()->assertJsonPath('code', 'UNAUTHENTICATED');

        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer garbage.token.here'])
            ->assertUnauthorized();
    }

    public function test_me_returns_profile_with_role_and_permissions(): void
    {
        $login = $this->login();

        $this->getJson('/api/v1/me', ['Authorization' => "Bearer {$login['access_token']}"])
            ->assertOk()
            ->assertJsonPath('data.role.name', 'member');
    }

    public function test_refresh_rotates_the_token(): void
    {
        $login = $this->login();

        $refreshed = $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $login['refresh_token']])
            ->assertOk()
            ->json();

        $this->assertNotSame($login['refresh_token'], $refreshed['refresh_token']);

        // The old token is now revoked…
        $this->assertNotNull(
            RefreshToken::where('token_hash', hash('sha256', $login['refresh_token']))->first()->revoked_at,
        );

        // …and the new one still works.
        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $refreshed['refresh_token']])->assertOk();
    }

    public function test_reusing_a_rotated_token_kills_the_whole_family(): void
    {
        $login = $this->login();

        $refreshed = $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $login['refresh_token']])->json();

        // Replay the OLD token — this is what a thief would do.
        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $login['refresh_token']])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'REFRESH_REJECTED');

        // The legitimate client's newer token must now be dead too.
        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $refreshed['refresh_token']])
            ->assertUnauthorized();
    }

    public function test_logout_revokes_every_session(): void
    {
        $login = $this->login();

        $this->postJson('/api/v1/auth/logout', [], ['Authorization' => "Bearer {$login['access_token']}"])
            ->assertOk();

        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $login['refresh_token']])
            ->assertUnauthorized();
    }

    /** @return array{access_token:string,refresh_token:string} */
    private function login(): array
    {
        $user = User::factory()->member()->create(['password' => 'super-secret-1']);

        return $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'super-secret-1',
        ])->assertOk()->json();
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Jwt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_list_users(): void
    {
        $member = User::factory()->member()->create();

        $this->getJson('/api/v1/users', $this->authHeader($member))
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN')
            ->assertJsonPath('errors.permission.0', 'users.view');
    }

    public function test_admin_can_list_users(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->first();

        $this->getJson('/api/v1/users', $this->authHeader($admin))
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'email']], 'links', 'meta']);
    }

    private function authHeader(User $user): array
    {
        return ['Authorization' => 'Bearer '.Jwt::issue($user->id)];
    }
}

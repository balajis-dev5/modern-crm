<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FollowUp;
use App\Models\User;
use App\Support\Jwt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_follow_up_requires_a_customer_or_a_lead(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/follow-ups', [
            'title' => 'Orphan task',
            'due_at' => now()->addDay()->toIso8601String(),
        ], $this->authHeader($user))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_id', 'lead_id']);
    }

    public function test_overdue_bucket_only_returns_open_past_due_items(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['owner_id' => $user->id]);

        FollowUp::factory()->create([
            'customer_id' => $customer->id,
            'assigned_to' => $user->id,
            'due_at' => now()->subDays(3),
        ]);
        FollowUp::factory()->done()->create([
            'customer_id' => $customer->id,
            'assigned_to' => $user->id,
            'due_at' => now()->subDays(3),
        ]);
        FollowUp::factory()->create([
            'customer_id' => $customer->id,
            'assigned_to' => $user->id,
            'due_at' => now()->addDays(3),
        ]);

        $this->getJson('/api/v1/follow-ups?bucket=overdue', $this->authHeader($user))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.overdue', true);
    }

    public function test_complete_stamps_done_at(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['owner_id' => $user->id]);
        $followUp = FollowUp::factory()->create([
            'customer_id' => $customer->id,
            'assigned_to' => $user->id,
        ]);

        $this->patchJson("/api/v1/follow-ups/{$followUp->id}/complete", [], $this->authHeader($user))
            ->assertOk();

        $this->assertNotNull($followUp->fresh()->done_at);
    }

    private function authHeader(User $user): array
    {
        return ['Authorization' => 'Bearer '.Jwt::issue($user->id)];
    }
}

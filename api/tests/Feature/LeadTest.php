<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Support\Jwt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_leads(): void
    {
        $this->getJson('/api/v1/leads')->assertUnauthorized();
    }

    public function test_lead_can_be_created_and_defaults_owner_to_creator(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/leads', [
            'name' => 'Priya Raman',
            'email' => 'priya@acme.test',
            'source' => 'referral',
            'deal_value' => 250000,
        ], $this->authHeader($user))
            ->assertCreated()
            ->assertJsonPath('data.stage', 'new')
            ->assertJsonPath('data.owner.id', $user->id);
    }

    public function test_lead_rejects_unknown_source(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/leads', [
            'name' => 'Bad Source',
            'source' => 'carrier_pigeon',
        ], $this->authHeader($user))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('source');
    }

    public function test_leads_can_be_filtered_by_stage(): void
    {
        $user = User::factory()->create();
        Lead::factory(3)->stage('qualified')->create(['owner_id' => $user->id]);
        Lead::factory(2)->stage('won')->create(['owner_id' => $user->id]);

        $this->getJson('/api/v1/leads?stage=qualified', $this->authHeader($user))
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_stage_change_writes_history(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create(['owner_id' => $user->id]);

        $this->patchJson("/api/v1/leads/{$lead->id}/stage", ['stage' => 'contacted'], $this->authHeader($user))
            ->assertOk()
            ->assertJsonPath('data.stage', 'contacted');

        $this->assertDatabaseHas('lead_stage_histories', [
            'lead_id' => $lead->id,
            'from_stage' => 'new',
            'to_stage' => 'contacted',
            'changed_by' => $user->id,
        ]);
    }

    public function test_same_stage_change_is_a_no_op(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create(['owner_id' => $user->id]);

        $this->patchJson("/api/v1/leads/{$lead->id}/stage", ['stage' => 'new'], $this->authHeader($user))
            ->assertOk();

        $this->assertDatabaseCount('lead_stage_histories', 0);
    }

    public function test_convert_promotes_lead_to_customer_and_marks_won(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->stage('proposal')->create([
            'owner_id' => $user->id,
            'company' => 'Acme Pvt Ltd',
        ]);

        $this->postJson("/api/v1/leads/{$lead->id}/convert", [], $this->authHeader($user))
            ->assertCreated()
            ->assertJsonPath('data.company', 'Acme Pvt Ltd')
            ->assertJsonPath('data.lead_id', $lead->id);

        $this->assertSame('won', $lead->fresh()->stage);
    }

    public function test_convert_twice_is_rejected(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create(['owner_id' => $user->id]);

        $this->postJson("/api/v1/leads/{$lead->id}/convert", [], $this->authHeader($user))->assertCreated();

        $this->postJson("/api/v1/leads/{$lead->id}/convert", [], $this->authHeader($user))
            ->assertUnprocessable()
            ->assertJsonPath('code', 'LEAD_ALREADY_CONVERTED');
    }

    private function authHeader(User $user): array
    {
        return ['Authorization' => 'Bearer '.Jwt::issue($user->id)];
    }
}

<?php

namespace Tests\Feature;

use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use App\Support\Jwt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_funnel_counts_leads_that_passed_each_stage(): void
    {
        $user = User::factory()->create();
        Lead::factory(4)->stage('new')->create(['owner_id' => $user->id]);
        Lead::factory(3)->stage('qualified')->create(['owner_id' => $user->id]);
        Lead::factory(2)->stage('won')->create(['owner_id' => $user->id]);
        Lead::factory(1)->stage('lost')->create(['owner_id' => $user->id]);

        $response = $this->getJson('/api/v1/analytics/funnel', $this->authHeader($user))
            ->assertOk()
            ->json('funnel');

        $byStage = collect($response)->keyBy('stage');

        $this->assertSame(10, $byStage['new']['count']);
        // qualified, won and lost leads all passed "contacted"
        $this->assertSame(6, $byStage['contacted']['count']);
        $this->assertSame(5, $byStage['qualified']['count']);
        $this->assertSame(2, $byStage['won']['count']);
    }

    public function test_dashboard_reports_kpis(): void
    {
        $user = User::factory()->create();
        Lead::factory(2)->stage('won')->create(['owner_id' => $user->id, 'deal_value' => 100000]);
        Lead::factory(2)->stage('lost')->create(['owner_id' => $user->id]);
        Lead::factory(1)->stage('proposal')->create(['owner_id' => $user->id, 'deal_value' => 300000]);

        $this->getJson('/api/v1/analytics/dashboard', $this->authHeader($user))
            ->assertOk()
            ->assertJsonPath('kpis.total_leads', 5)
            ->assertJsonPath('kpis.conversion_rate', 50)
            ->assertJsonPath('kpis.pipeline_value', 300000)
            ->assertJsonPath('kpis.won_value', 200000)
            ->assertJsonCount(8, 'trend');
    }

    public function test_sources_report_includes_win_rate_inputs(): void
    {
        $user = User::factory()->create();
        Lead::factory(3)->stage('won')->create(['owner_id' => $user->id, 'source' => 'referral', 'deal_value' => 50000]);
        Lead::factory(2)->stage('new')->create(['owner_id' => $user->id, 'source' => 'ads']);

        $sources = collect($this->getJson('/api/v1/analytics/sources', $this->authHeader($user))
            ->assertOk()
            ->json('sources'))->keyBy('source');

        $this->assertSame(3, (int) $sources['referral']['leads']);
        $this->assertSame(3, (int) $sources['referral']['won']);
        $this->assertSame(150000, (int) $sources['referral']['won_value']);
        $this->assertSame(0, (int) $sources['ads']['won']);
    }

    public function test_overdue_follow_ups_feed_the_dashboard(): void
    {
        $user = User::factory()->create();
        $lead = Lead::factory()->create(['owner_id' => $user->id]);
        FollowUp::factory(2)->create([
            'lead_id' => $lead->id,
            'assigned_to' => $user->id,
            'due_at' => now()->subDay(),
        ]);

        $this->getJson('/api/v1/analytics/dashboard', $this->authHeader($user))
            ->assertOk()
            ->assertJsonPath('kpis.overdue_follow_ups', 2);
    }

    private function authHeader(User $user): array
    {
        return ['Authorization' => 'Bearer '.Jwt::issue($user->id)];
    }
}

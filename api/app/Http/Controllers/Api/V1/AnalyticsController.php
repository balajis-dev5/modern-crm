<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FollowUp;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    /**
     * KPI cards + 8-week lead trend for the dashboard, computed server-side.
     */
    public function dashboard(): JsonResponse
    {
        $totals = Lead::query()
            ->selectRaw('count(*) as total_leads')
            ->selectRaw('sum(case when created_at >= ? then 1 else 0 end) as new_leads_30d', [now()->subDays(30)])
            ->selectRaw("sum(case when stage = 'won' then 1 else 0 end) as won_leads")
            ->selectRaw("sum(case when stage not in ('won', 'lost') then deal_value else 0 end) as pipeline_value")
            ->selectRaw("sum(case when stage = 'won' then deal_value else 0 end) as won_value")
            ->first();

        $weeks = collect(range(7, 0))->map(fn (int $back) => [
            'start' => now()->subWeeks($back)->startOfWeek(),
            'end' => now()->subWeeks($back)->endOfWeek(),
        ]);

        $trend = $weeks->map(fn (array $week) => [
            'week' => $week['start']->format('M j'),
            'leads' => Lead::whereBetween('created_at', [$week['start'], $week['end']])->count(),
            'won' => Lead::where('stage', 'won')
                ->whereBetween('updated_at', [$week['start'], $week['end']])
                ->count(),
        ]);

        $closed = (int) $totals->won_leads + Lead::where('stage', 'lost')->count();

        return response()->json([
            'kpis' => [
                'total_leads' => (int) $totals->total_leads,
                'new_leads_30d' => (int) $totals->new_leads_30d,
                'conversion_rate' => $closed > 0 ? round($totals->won_leads / $closed * 100, 1) : 0.0,
                'pipeline_value' => (int) $totals->pipeline_value,
                'won_value' => (int) $totals->won_value,
                'overdue_follow_ups' => FollowUp::overdue()->count(),
            ],
            'trend' => $trend,
        ]);
    }

    /**
     * Conversion funnel in ONE query via conditional aggregation —
     * a lead currently in "proposal" has passed contacted and qualified.
     */
    public function funnel(): JsonResponse
    {
        $stageRank = "case stage
            when 'new' then 0 when 'contacted' then 1 when 'qualified' then 2
            when 'proposal' then 3 when 'won' then 4 else -1 end";

        $row = Lead::query()
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when ({$stageRank}) >= 1 or stage = 'lost' then 1 else 0 end) as contacted")
            ->selectRaw("sum(case when ({$stageRank}) >= 2 then 1 else 0 end) as qualified")
            ->selectRaw("sum(case when ({$stageRank}) >= 3 then 1 else 0 end) as proposal")
            ->selectRaw("sum(case when ({$stageRank}) >= 4 then 1 else 0 end) as won")
            ->first();

        return response()->json([
            'funnel' => [
                ['stage' => 'new', 'count' => (int) $row->total],
                ['stage' => 'contacted', 'count' => (int) $row->contacted],
                ['stage' => 'qualified', 'count' => (int) $row->qualified],
                ['stage' => 'proposal', 'count' => (int) $row->proposal],
                ['stage' => 'won', 'count' => (int) $row->won],
            ],
        ]);
    }

    /**
     * Which sources actually convert, not just which bring volume.
     */
    public function sources(): JsonResponse
    {
        $rows = Lead::query()
            ->select('source')
            ->selectRaw('count(*) as leads')
            ->selectRaw("sum(case when stage = 'won' then 1 else 0 end) as won")
            ->selectRaw("sum(case when stage = 'won' then deal_value else 0 end) as won_value")
            ->groupBy('source')
            ->orderByDesc('leads')
            ->get();

        return response()->json(['sources' => $rows]);
    }

    /**
     * Owner leaderboard by closed value.
     */
    public function leaderboard(): JsonResponse
    {
        $rows = Lead::query()
            ->join('users', 'users.id', '=', 'leads.owner_id')
            ->select('users.id', 'users.name')
            ->selectRaw('count(*) as leads')
            ->selectRaw("sum(case when stage = 'won' then 1 else 0 end) as won")
            ->selectRaw("sum(case when stage = 'won' then deal_value else 0 end) as won_value")
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('won_value')
            ->limit(10)
            ->get();

        return response()->json(['leaderboard' => $rows]);
    }
}

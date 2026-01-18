<?php

namespace App\Http\Controllers\Api\Ads;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\AdDailyStat;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Get summary report
     * 
     * GET /api/ads/reports/summary
     */
    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'group_by' => 'sometimes|in:day,placement,campaign',
            'campaign_id' => 'sometimes|integer',
        ]);

        $user = $request->user();

        // Get user's campaigns
        $campaignIds = AdCampaign::whereHas('adAccount.entity', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->pluck('id');

        if ($request->has('campaign_id')) {
            $campaignIds = $campaignIds->intersect([$request->input('campaign_id')]);
        }

        $query = AdDailyStat::whereIn('campaign_id', $campaignIds)
            ->whereBetween('date', [$validated['from'], $validated['to']]);

        $groupBy = $validated['group_by'] ?? 'day';

        if ($groupBy === 'day') {
            $stats = $query->selectRaw('
                    date,
                    SUM(impressions) as impressions,
                    SUM(clicks) as clicks,
                    SUM(leads) as leads,
                    SUM(spend) as spend
                ')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } elseif ($groupBy === 'placement') {
            $stats = $query->selectRaw('
                    placement,
                    SUM(impressions) as impressions,
                    SUM(clicks) as clicks,
                    SUM(leads) as leads,
                    SUM(spend) as spend
                ')
                ->groupBy('placement')
                ->get();
        } else { // campaign
            $stats = $query->selectRaw('
                    campaign_id,
                    SUM(impressions) as impressions,
                    SUM(clicks) as clicks,
                    SUM(leads) as leads,
                    SUM(spend) as spend
                ')
                ->groupBy('campaign_id')
                ->get();
        }

        // Calculate totals
        $totals = [
            'impressions' => $stats->sum('impressions'),
            'clicks' => $stats->sum('clicks'),
            'leads' => $stats->sum('leads'),
            'spend' => $stats->sum('spend'),
        ];

        $totals['ctr'] = $totals['impressions'] > 0 
            ? round(($totals['clicks'] / $totals['impressions']) * 100, 2)
            : 0;

        $totals['cpc'] = $totals['clicks'] > 0 
            ? round($totals['spend'] / $totals['clicks'], 2)
            : 0;

        $totals['cpm'] = $totals['impressions'] > 0 
            ? round(($totals['spend'] / $totals['impressions']) * 1000, 2)
            : 0;

        return response()->json([
            'data' => $totals,
            'breakdown' => $stats,
        ]);
    }

    /**
     * Get campaign report
     * 
     * GET /api/ads/reports/campaign/{id}
     */
    public function campaign(Request $request, int $id): JsonResponse
    {
        $campaign = AdCampaign::findOrFail($id);
        
        $this->authorize('view', $campaign);

        $validated = $request->validate([
            'from' => 'sometimes|date',
            'to' => 'sometimes|date',
        ]);

        $query = AdDailyStat::where('campaign_id', $id);

        if ($request->has('from')) {
            $query->where('date', '>=', $validated['from']);
        }

        if ($request->has('to')) {
            $query->where('date', '<=', $validated['to']);
        }

        // Timeseries by day
        $timeseries = $query->selectRaw('
                date,
                SUM(impressions) as impressions,
                SUM(clicks) as clicks,
                SUM(leads) as leads,
                SUM(spend) as spend
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Breakdown by placement
        $byPlacement = AdDailyStat::where('campaign_id', $id)
            ->selectRaw('
                placement,
                SUM(impressions) as impressions,
                SUM(clicks) as clicks,
                SUM(leads) as leads,
                SUM(spend) as spend
            ')
            ->groupBy('placement')
            ->get();

        // Breakdown by creative
        $byCreative = AdDailyStat::where('campaign_id', $id)
            ->whereNotNull('creative_id')
            ->selectRaw('
                creative_id,
                SUM(impressions) as impressions,
                SUM(clicks) as clicks,
                SUM(leads) as leads,
                SUM(spend) as spend
            ')
            ->groupBy('creative_id')
            ->orderByDesc('spend')
            ->get();

        return response()->json([
            'data' => [
                'campaign' => $campaign,
                'timeseries' => $timeseries,
                'by_placement' => $byPlacement,
                'by_creative' => $byCreative,
            ],
        ]);
    }
}

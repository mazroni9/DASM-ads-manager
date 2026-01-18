<?php

namespace App\Services\Ads;

use App\Models\AdEvent;
use App\Models\AdCreative;
use App\Models\AdCampaign;
use App\Services\Ads\AdServingService;
use App\Services\Ads\AntiFraudService;
use App\Services\Ads\BillingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrackingService
{
    protected AdServingService $servingService;
    protected AntiFraudService $antiFraudService;
    protected BillingService $billingService;

    public function __construct(
        AdServingService $servingService,
        AntiFraudService $antiFraudService,
        BillingService $billingService
    ) {
        $this->servingService = $servingService;
        $this->antiFraudService = $antiFraudService;
        $this->billingService = $billingService;
    }

    /**
     * Track an event (impression, click, lead)
     */
    public function track(string $trackingToken, string $eventType, array $context): array
    {
        // Validate token
        $payload = $this->servingService->validateTrackingToken($trackingToken);
        
        if (!$payload) {
            return [
                'ok' => false,
                'charged' => 0,
                'is_valid' => false,
                'error' => 'Invalid or expired token',
            ];
        }

        $creativeId = $payload['creative_id'];
        $campaignId = $payload['campaign_id'];
        $placement = $payload['placement'];
        $servedAt = $payload['served_at'];

        // Get creative and campaign
        $creative = AdCreative::find($creativeId);
        $campaign = AdCampaign::find($campaignId);

        if (!$creative || !$campaign) {
            return [
                'ok' => false,
                'charged' => 0,
                'is_valid' => false,
                'error' => 'Creative or campaign not found',
            ];
        }

        // Anti-fraud checks
        $fraudCheck = $this->antiFraudService->validateEvent($eventType, $context, $creativeId);
        
        if (!$fraudCheck['valid']) {
            // Log but don't charge
            AdEvent::create([
                'campaign_id' => $campaignId,
                'creative_id' => $creativeId,
                'placement' => $placement,
                'position' => $context['position'] ?? null,
                'event_type' => $eventType,
                'cost_charged' => 0,
                'user_id' => $context['user_id'] ?? null,
                'session_id_hash' => hash('sha256', $context['session_id'] ?? ''),
                'ip_hash' => isset($context['ip']) ? hash('sha256', $context['ip']) : null,
                'user_agent_hash' => isset($context['user_agent']) ? hash('sha256', $context['user_agent']) : null,
                'served_at' => $servedAt,
                'created_at' => now(),
                'context' => $context,
                'is_valid' => false,
                'invalid_reason' => $fraudCheck['reason'],
            ]);

            return [
                'ok' => false,
                'charged' => 0,
                'is_valid' => false,
                'reason' => $fraudCheck['reason'],
            ];
        }

        // Calculate cost (if applicable)
        $cost = 0;
        
        if ($eventType === 'click' && $campaign->pricing_model === 'CPC') {
            // CPC: charge per click
            $cost = $this->calculateCPCCost($campaign);
            
            // Charge wallet
            $charged = $this->billingService->charge($campaign, $cost, 'click', $creativeId);
            
            if (!$charged['success']) {
                return [
                    'ok' => false,
                    'charged' => 0,
                    'is_valid' => false,
                    'error' => $charged['error'] ?? 'Failed to charge',
                ];
            }
            
            $cost = $charged['amount'];
        } elseif ($eventType === 'impression' && $campaign->pricing_model === 'CPM') {
            // CPM: accumulate impressions, charge in batches
            // For now, charge micro-amount per impression (will be batched later)
            $cost = $this->calculateCPMCost($campaign) / 1000; // Cost per impression
            
            // Charge wallet
            $charged = $this->billingService->charge($campaign, $cost, 'impression', $creativeId);
            
            if (!$charged['success']) {
                return [
                    'ok' => false,
                    'charged' => 0,
                    'is_valid' => false,
                    'error' => $charged['error'] ?? 'Failed to charge',
                ];
            }
            
            $cost = $charged['amount'];
        }

        // Create event
        $event = AdEvent::create([
            'campaign_id' => $campaignId,
            'creative_id' => $creativeId,
            'placement' => $placement,
            'position' => $context['position'] ?? null,
            'event_type' => $eventType,
            'cost_charged' => $cost,
            'user_id' => $context['user_id'] ?? null,
            'session_id_hash' => hash('sha256', $context['session_id'] ?? ''),
            'ip_hash' => isset($context['ip']) ? hash('sha256', $context['ip']) : null,
            'user_agent_hash' => isset($context['user_agent']) ? hash('sha256', $context['user_agent']) : null,
            'served_at' => $servedAt,
            'created_at' => now(),
            'context' => $context,
            'is_valid' => true,
        ]);

        // Update daily stats (async or sync)
        $this->updateDailyStats($event);

        return [
            'ok' => true,
            'charged' => $cost,
            'is_valid' => true,
            'event_id' => $event->id,
        ];
    }

    /**
     * Calculate CPC cost
     */
    protected function calculateCPCCost(AdCampaign $campaign): float
    {
        // Use median CPC for placement as default
        // TODO: Allow campaign to set explicit bid_amount
        $placement = 'search_listings'; // This should come from context
        $medianCPC = config("ads.median_bids.{$placement}.CPC", 1.5);
        
        return $medianCPC;
    }

    /**
     * Calculate CPM cost per 1000 impressions
     */
    protected function calculateCPMCost(AdCampaign $campaign): float
    {
        $placement = 'search_listings'; // This should come from context
        $medianCPM = config("ads.median_bids.{$placement}.CPM", 15.0);
        
        return $medianCPM;
    }

    /**
     * Update daily stats (can be done async via queue)
     */
    protected function updateDailyStats(AdEvent $event): void
    {
        $date = $event->created_at->format('Y-m-d');

        DB::table('ad_daily_stats')->upsert([
            [
                'date' => $date,
                'campaign_id' => $event->campaign_id,
                'creative_id' => $event->creative_id,
                'placement' => $event->placement,
                'impressions' => $event->event_type === 'impression' ? 1 : 0,
                'clicks' => $event->event_type === 'click' ? 1 : 0,
                'leads' => $event->event_type === 'lead' ? 1 : 0,
                'spend' => $event->cost_charged,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], [
            'date',
            'campaign_id',
            'creative_id',
            'placement',
        ], [
            'impressions' => DB::raw("impressions + " . ($event->event_type === 'impression' ? 1 : 0)),
            'clicks' => DB::raw("clicks + " . ($event->event_type === 'click' ? 1 : 0)),
            'leads' => DB::raw("leads + " . ($event->event_type === 'lead' ? 1 : 0)),
            'spend' => DB::raw("spend + " . $event->cost_charged),
            'updated_at' => now(),
        ]);
    }
}

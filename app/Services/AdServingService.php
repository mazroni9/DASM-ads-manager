<?php

namespace App\Services\Ads;

use App\Models\AdCampaign;
use App\Models\AdCreative;
use App\Services\DasmIntegration\DasmApiClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdServingService
{
    protected AdRankingService $rankingService;
    protected TargetingValidatorService $targetingValidator;
    protected DasmApiClient $dasmClient;

    public function __construct(
        AdRankingService $rankingService,
        TargetingValidatorService $targetingValidator,
        DasmApiClient $dasmClient
    ) {
        $this->rankingService = $rankingService;
        $this->targetingValidator = $targetingValidator;
        $this->dasmClient = $dasmClient;
    }

    /**
     * Get ads for display
     * 
     * @param array $context Placement context
     * @param int $limit Number of ads to return
     * @return array
     */
    public function serveAds(array $context, int $limit = 3): array
    {
        $placement = $context['placement'] ?? 'search_listings';

        // Filter eligible campaigns
        $creatives = $this->filterEligibleCreatives($context);

        if ($creatives->isEmpty()) {
            return [];
        }

        // Rank creatives
        $ranked = $this->rankingService->rankCreatives($creatives, $context);

        // Apply frequency caps
        $ranked = $this->applyFrequencyCaps($ranked, $context);

        // Apply diversity (optional - prevent same owner multiple times)
        $ranked = $this->applyDiversity($ranked);

        // Select top K
        $selected = $ranked->take($limit);

        // Format and add tracking tokens
        return $selected->map(function ($item) use ($context) {
            return $this->formatAd($item['creative'], $context);
        })->toArray();
    }

    /**
     * Filter eligible creatives
     */
    protected function filterEligibleCreatives(array $context): \Illuminate\Support\Collection
    {
        $now = now();

        return AdCreative::with(['campaign.adAccount.wallet', 'campaign', 'car'])
            ->whereHas('campaign', function ($query) use ($now) {
                $query->where('status', 'active')
                    ->where(function ($q) use ($now) {
                        $q->whereNull('start_at')
                          ->orWhere('start_at', '<=', $now);
                    })
                    ->where(function ($q) use ($now) {
                        $q->whereNull('end_at')
                          ->orWhere('end_at', '>=', $now);
                    });
            })
            ->where('status', 'approved')
            ->get()
            ->filter(function ($creative) use ($context) {
                // Check budget availability
                $wallet = $creative->campaign->adAccount->wallet;
                if (!$wallet || $wallet->balance_available <= 0) {
                    return false;
                }

                // Check daily budget
                if (!$creative->campaign->hasRemainingDailyBudget()) {
                    return false;
                }

                // Check targeting match
                $targeting = $creative->campaign->targeting;
                if (!$this->targetingValidator->matches($targeting, $context)) {
                    return false;
                }

                // Check schedule (time of day, day of week)
                if (!$this->checkSchedule($targeting)) {
                    return false;
                }

                // Check quality threshold (exclude very low quality)
                $quality = $this->calculateQuickQuality($creative);
                if ($quality < 0.2) {
                    return false;
                }

                return true;
            });
    }

    /**
     * Check if campaign schedule allows serving now
     */
    protected function checkSchedule(array $targeting): bool
    {
        $schedule = $targeting['schedule'] ?? [];
        
        if (empty($schedule)) {
            return true; // No schedule = always serve
        }

        $timezone = $schedule['timezone'] ?? 'Asia/Riyadh';
        $now = now()->setTimezone($timezone);

        // Check days of week
        if (isset($schedule['days_of_week']) && !empty($schedule['days_of_week'])) {
            $dayOfWeek = $now->dayOfWeek; // 0=Sunday, 6=Saturday
            if (!in_array($dayOfWeek, $schedule['days_of_week'])) {
                return false;
            }
        }

        // Check hours
        if (isset($schedule['hours'])) {
            $hour = (int) $now->format('H');
            $from = $schedule['hours']['from'] ?? 0;
            $to = $schedule['hours']['to'] ?? 23;

            if ($hour < $from || $hour > $to) {
                return false;
            }
        }

        return true;
    }

    /**
     * Quick quality check (without full calculation)
     */
    protected function calculateQuickQuality(AdCreative $creative): float
    {
        // Simple check: if creative is approved and campaign is active, assume decent quality
        // Full quality calculation is done in AdRankingService
        return 0.5; // Neutral for quick filter
    }

    /**
     * Apply frequency caps
     */
    protected function applyFrequencyCaps($ranked, array $context): \Illuminate\Support\Collection
    {
        $sessionId = $context['session_id'] ?? null;
        $userId = $context['user_id'] ?? null;

        if (!$sessionId && !$userId) {
            return $ranked; // No way to track, skip caps
        }

        $targeting = $ranked->first()['creative']->campaign->targeting ?? [];
        $frequencyCap = $targeting['schedule']['frequency_cap'] ?? [
            'impressions_per_user_per_day' => 5,
            'clicks_per_user_per_day' => 2,
        ];

        $maxImpressions = $frequencyCap['impressions_per_user_per_day'] ?? 5;

        // Get today's impressions for this user/session
        $todayStart = now()->startOfDay();
        
        $query = \App\Models\AdEvent::where('event_type', 'impression')
            ->where('created_at', '>=', $todayStart);

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id_hash', hash('sha256', $sessionId));
        }

        $impressionsToday = $query->groupBy('creative_id')
            ->pluck('creative_id')
            ->toArray();

        // Filter out creatives that exceeded cap
        return $ranked->reject(function ($item) use ($impressionsToday, $maxImpressions) {
            $creativeId = $item['creative']->id;
            
            // Count impressions for this creative today
            $count = collect($impressionsToday)->filter(fn($id) => $id === $creativeId)->count();
            
            return $count >= $maxImpressions;
        });
    }

    /**
     * Apply diversity (prevent same owner multiple times)
     */
    protected function applyDiversity($ranked): \Illuminate\Support\Collection
    {
        $seenOwners = [];
        $maxSameOwner = 2; // Max 2 ads from same owner

        return $ranked->reject(function ($item) use (&$seenOwners, $maxSameOwner) {
            $creative = $item['creative'];
            $car = $creative->car;
            
            if (!$car) {
                return false; // Keep if no car data
            }

            $ownerId = $car->owner_entity_id ?? null;
            
            if (!$ownerId) {
                return false; // Keep if no owner
            }

            $count = $seenOwners[$ownerId] ?? 0;
            
            if ($count >= $maxSameOwner) {
                return true; // Reject if exceeded
            }

            $seenOwners[$ownerId] = $count + 1;
            return false; // Keep
        });
    }

    /**
     * Format ad with tracking token
     */
    protected function formatAd(AdCreative $creative, array $context): array
    {
        $campaign = $creative->campaign;
        $car = $this->dasmClient->getCar($creative->car_id);

        // Generate tracking token
        $trackingToken = $this->generateTrackingToken($creative, $context);

        // Format rendered data
        $rendered = [
            'headline' => $creative->headline ?? ($car['make'] ?? '') . ' ' . ($car['model'] ?? ''),
            'subtitle' => $creative->subtitle ?? null,
            'image_url' => $car['images'][0]['url'] ?? null,
            'price' => $car['price'] ?? null,
            'city' => $car['city'] ?? null,
            'cta' => $creative->cta,
        ];

        return [
            'creative_id' => $creative->id,
            'campaign_id' => $campaign->id,
            'car_id' => $creative->car_id,
            'placement' => $context['placement'] ?? 'search_listings',
            'position' => $context['position'] ?? null,
            'tracking_token' => $trackingToken,
            'rendered' => $rendered,
        ];
    }

    /**
     * Generate HMAC tracking token
     */
    protected function generateTrackingToken(AdCreative $creative, array $context): string
    {
        $payload = [
            'creative_id' => $creative->id,
            'campaign_id' => $creative->campaign_id,
            'placement' => $context['placement'] ?? 'search_listings',
            'served_at' => now()->toIso8601String(),
            'session_id' => hash('sha256', $context['session_id'] ?? ''),
        ];

        $payloadJson = json_encode($payload);
        $secret = config('ads.tracking.secret_key');
        
        $signature = hash_hmac('sha256', $payloadJson, $secret);
        
        // Encode payload + signature as base64
        $token = base64_encode($payloadJson . '|' . $signature);

        return $token;
    }

    /**
     * Validate tracking token
     */
    public function validateTrackingToken(string $token): ?array
    {
        try {
            $decoded = base64_decode($token);
            
            if (!$decoded) {
                return null;
            }

            list($payloadJson, $signature) = explode('|', $decoded, 2);

            $secret = config('ads.tracking.secret_key');
            $expectedSignature = hash_hmac('sha256', $payloadJson, $secret);

            if (!hash_equals($expectedSignature, $signature)) {
                return null; // Invalid signature
            }

            $payload = json_decode($payloadJson, true);

            if (!$payload) {
                return null;
            }

            // Check TTL
            $ttl = config('ads.tracking.token_ttl_seconds', 600);
            $servedAt = Carbon::parse($payload['served_at']);
            
            if ($servedAt->addSeconds($ttl)->isPast()) {
                return null; // Token expired
            }

            return $payload;
        } catch (\Exception $e) {
            Log::error('Failed to validate tracking token', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

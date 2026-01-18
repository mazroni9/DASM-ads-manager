<?php

namespace App\Services\Ads;

use App\Models\AdCampaign;
use App\Models\AdCreative;
use App\Services\DasmIntegration\DasmApiClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AdRankingService
{
    protected TargetingValidatorService $targetingValidator;
    protected DasmApiClient $dasmClient;

    protected float $bidWeight;
    protected float $qualityWeight;
    protected float $relevanceWeight;

    public function __construct(
        TargetingValidatorService $targetingValidator,
        DasmApiClient $dasmClient
    ) {
        $this->targetingValidator = $targetingValidator;
        $this->dasmClient = $dasmClient;

        $this->bidWeight = config('ads.ranking.bid_weight', 0.7);
        $this->qualityWeight = config('ads.ranking.quality_weight', 1.0);
        $this->relevanceWeight = config('ads.ranking.relevance_weight', 1.2);
    }

    /**
     * Calculate final score for a creative
     * 
     * @param AdCreative $creative
     * @param array $context Placement context (city, user_type, search_filters, etc.)
     * @return float
     */
    public function calculateScore(AdCreative $creative, array $context): float
    {
        $campaign = $creative->campaign;

        // Calculate components
        $bidNorm = $this->calculateBidNorm($campaign, $context['placement'] ?? 'search_listings');
        $relevance = $this->calculateRelevance($campaign, $creative, $context);
        $quality = $this->calculateQuality($creative, $context['placement'] ?? 'search_listings');

        // Apply formula: final_score = (bid_norm^bid_weight) * (quality^quality_weight) * (relevance^relevance_weight)
        $finalScore = pow($bidNorm, $this->bidWeight) 
                    * pow($quality, $this->qualityWeight) 
                    * pow($relevance, $this->relevanceWeight);

        return max(0, $finalScore);
    }

    /**
     * Calculate normalized bid score
     */
    protected function calculateBidNorm(AdCampaign $campaign, string $placement): float
    {
        $medianBids = config("ads.median_bids.{$placement}", [
            'CPC' => 1.5,
            'CPM' => 15.0,
        ]);

        if ($campaign->pricing_model === 'CPC') {
            $median = $medianBids['CPC'] ?? 1.5;
            // For now, use median. Later, campaign can have explicit bid_amount
            $bid = $median; // Default to median if no explicit bid
            
            // TODO: Add campaign.bid_amount column if needed
            // $bid = $campaign->bid_amount ?? $median;
            
            return $bid / $median;
        } else { // CPM
            $median = $medianBids['CPM'] ?? 15.0;
            $bid = $median; // Default
            
            return $bid / $median;
        }
    }

    /**
     * Calculate relevance score (0..1)
     */
    protected function calculateRelevance(AdCampaign $campaign, AdCreative $creative, array $context): float
    {
        $targeting = $campaign->targeting;
        
        $geoMatch = $this->calculateGeoMatch($targeting, $context);
        $placementMatch = $this->calculatePlacementMatch($targeting, $context);
        $intentMatch = $this->calculateIntentMatch($creative, $context);
        $audienceMatch = $this->calculateAudienceMatch($targeting, $context);

        // Weighted sum
        return (0.35 * $geoMatch) 
             + (0.25 * $placementMatch) 
             + (0.30 * $intentMatch) 
             + (0.10 * $audienceMatch);
    }

    /**
     * Calculate geo match (0..1)
     */
    protected function calculateGeoMatch(array $targeting, array $context): float
    {
        if (!isset($targeting['geo']) || !isset($context['city'])) {
            return 1.0; // No geo targeting = match all
        }

        $geo = $targeting['geo'];
        $city = $context['city'];
        $region = $context['region'] ?? null;

        // Check exclude first
        $excludeCities = $geo['exclude_cities'] ?? [];
        if (in_array($city, $excludeCities)) {
            return 0.0;
        }

        // Check include cities
        $includeCities = $geo['include_cities'] ?? [];
        if (in_array($city, $includeCities)) {
            return 1.0;
        }

        // Check regions
        $includeRegions = $geo['include_regions'] ?? [];
        if ($region && in_array($region, $includeRegions)) {
            return 0.5;
        }

        // Check radius (if provided)
        if (isset($geo['radius_km']) && isset($geo['center'])) {
            // TODO: Implement distance calculation if needed
            // For now, return 0.5 if radius targeting exists
            return 0.5;
        }

        // No match
        return !empty($includeCities) || !empty($includeRegions) ? 0.0 : 1.0;
    }

    /**
     * Calculate placement match (0 or 1)
     */
    protected function calculatePlacementMatch(array $targeting, array $context): float
    {
        $placement = $context['placement'] ?? 'search_listings';
        
        $placements = $targeting['placements'] ?? [];
        $exclude = $placements['exclude'] ?? [];
        $include = $placements['include'] ?? ['search_listings', 'car_details'];

        // Check exclude
        if (in_array($placement, $exclude)) {
            return 0.0;
        }

        // Check include
        if (empty($include) || in_array($placement, $include)) {
            return 1.0;
        }

        return 0.0;
    }

    /**
     * Calculate intent match (0..1)
     * Based on search filters vs car attributes
     */
    protected function calculateIntentMatch(AdCreative $creative, array $context): float
    {
        $match = 0.0;

        // Get car data from DASMe
        $car = $this->dasmClient->getCar($creative->car_id);
        
        if (!$car) {
            return 0.5; // Neutral if car data unavailable
        }

        // Match make
        $searchMake = $context['search_make'] ?? null;
        if ($searchMake && isset($car['make']) && strtolower($car['make']) === strtolower($searchMake)) {
            $match += 0.4;
        }

        // Match model
        $searchModel = $context['search_model'] ?? null;
        if ($searchModel && isset($car['model']) && strtolower($car['model']) === strtolower($searchModel)) {
            $match += 0.4;
        }

        // Match price range
        $priceRange = $context['price_range'] ?? null;
        if ($priceRange && isset($car['price'])) {
            $carPrice = (float) $car['price'];
            $minPrice = $priceRange[0] ?? 0;
            $maxPrice = $priceRange[1] ?? PHP_INT_MAX;
            
            if ($carPrice >= $minPrice && $carPrice <= $maxPrice) {
                $match += 0.2;
            }
        }

        return min($match, 1.0);
    }

    /**
     * Calculate audience match (0 or 1)
     */
    protected function calculateAudienceMatch(array $targeting, array $context): float
    {
        $userType = $context['user_type'] ?? 'guest';

        $audience = $targeting['audience'] ?? [];
        $excludeTypes = $audience['exclude_user_types'] ?? [];
        $includeTypes = $audience['user_types'] ?? ['registered', 'verified_buyer'];

        // Check exclude
        if (in_array($userType, $excludeTypes)) {
            return 0.0;
        }

        // Check include
        if (empty($includeTypes) || in_array($userType, $includeTypes)) {
            return 1.0;
        }

        return 0.0;
    }

    /**
     * Calculate quality score (0..1)
     */
    protected function calculateQuality(AdCreative $creative, string $placement): float
    {
        $carPageQuality = $this->calculateCarPageQuality($creative);
        $ctrNorm = $this->calculateCtrNorm($creative, $placement);
        $complaintRate = $this->calculateComplaintRate($creative);

        $quality = (0.4 * $carPageQuality) 
                 + (0.4 * $ctrNorm) 
                 + (0.2 * (1 - $complaintRate));

        return max(0, min(1, $quality));
    }

    /**
     * Calculate car page quality (0..1)
     */
    protected function calculateCarPageQuality(AdCreative $creative): float
    {
        // Get car data from DASMe
        $car = $this->dasmClient->getCar($creative->car_id);
        
        if (!$car) {
            return 0.5; // Neutral if car unavailable
        }

        $score = 0.0;

        // Images count (assume 5+ = good)
        $imagesCount = isset($car['images']) ? count($car['images']) : 0;
        if ($imagesCount >= 5) {
            $score += 0.2;
        }

        // Inspection report exists
        if (isset($car['inspection_report']) && $car['inspection_report']) {
            $score += 0.2;
        }

        // Price and data complete
        if (isset($car['price']) && isset($car['make']) && isset($car['model'])) {
            $score += 0.2;
        }

        // Seller verified (assume verified if entity exists)
        // This would need to be checked in DASMe
        // For now, assume good if entity_id exists
        if (isset($car['owner_entity_id'])) {
            $score += 0.2;
        }

        // Page performance (assume good for now)
        $score += 0.2;

        return min($score, 1.0);
    }

    /**
     * Calculate normalized CTR (0..1)
     */
    protected function calculateCtrNorm(AdCreative $creative, string $placement): float
    {
        $avgCtr = config("ads.avg_ctr.{$placement}", 2.0); // percentage

        // Get historical CTR from ad_daily_stats
        $stats = Cache::remember("creative_ctr_{$creative->id}_{$placement}", 3600, function() use ($creative, $placement) {
            return \App\Models\AdDailyStat::where('creative_id', $creative->id)
                ->where('placement', $placement)
                ->selectRaw('
                    SUM(impressions) as total_impressions,
                    SUM(clicks) as total_clicks,
                    CASE 
                        WHEN SUM(impressions) > 0 
                        THEN (SUM(clicks)::float / SUM(impressions)::float) * 100 
                        ELSE 0 
                    END as ctr
                ')
                ->first();
        });

        if (!$stats || $stats->total_impressions < 100) {
            // Not enough data, use neutral score
            return 0.5;
        }

        $historicalCtr = (float) $stats->ctr;

        // Normalize: ctr_norm = min(historical_ctr / avg_ctr * 2, 1.0)
        $ctrNorm = min(($historicalCtr / $avgCtr) * 2, 1.0);

        return max(0, $ctrNorm);
    }

    /**
     * Calculate complaint rate (0..1)
     */
    protected function calculateComplaintRate(AdCreative $creative): float
    {
        // Get total impressions
        $totalImpressions = \App\Models\AdDailyStat::where('creative_id', $creative->id)
            ->sum('impressions');

        if ($totalImpressions === 0) {
            return 0.0; // No complaints if no impressions
        }

        // TODO: Add complaints tracking table or use meta field
        // For now, assume no complaints
        $totalComplaints = 0;

        return min($totalComplaints / $totalImpressions, 1.0);
    }

    /**
     * Rank creatives by score (highest first)
     * 
     * @param \Illuminate\Support\Collection $creatives
     * @param array $context
     * @return \Illuminate\Support\Collection
     */
    public function rankCreatives($creatives, array $context)
    {
        return $creatives->map(function ($creative) use ($context) {
            $score = $this->calculateScore($creative, $context);
            
            return [
                'creative' => $creative,
                'score' => $score,
            ];
        })->sortByDesc('score')->values();
    }
}

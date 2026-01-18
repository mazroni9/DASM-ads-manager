<?php

namespace App\Services\DasmIntegration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DasmApiClient
{
    protected string $baseUrl;
    protected string $token;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('dasm.api_base_url');
        $this->token = config('dasm.api_token');
        $this->timeout = config('dasm.timeout', 30);
    }

    /**
     * Get user data from DASMe
     */
    public function getUser(int $userId): ?array
    {
        return Cache::remember("dasm_user_{$userId}", 3600, function() use ($userId) {
            try {
                $response = Http::withToken($this->token)
                    ->timeout($this->timeout)
                    ->get("{$this->baseUrl}/users/{$userId}");

                if ($response->successful()) {
                    return $response->json('data');
                }

                Log::warning("DASMe API: Failed to fetch user {$userId}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error("DASMe API: Exception fetching user {$userId}", [
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Get car data from DASMe
     */
    public function getCar(int $carId): ?array
    {
        return Cache::remember("dasm_car_{$carId}", 1800, function() use ($carId) {
            try {
                $response = Http::withToken($this->token)
                    ->timeout($this->timeout)
                    ->get("{$this->baseUrl}/cars/{$carId}");

                if ($response->successful()) {
                    return $response->json('data');
                }

                return null;
            } catch (\Exception $e) {
                Log::error("DASMe API: Exception fetching car {$carId}", [
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Get entity (owner) data from DASMe
     */
    public function getEntity(int $entityId): ?array
    {
        return Cache::remember("dasm_entity_{$entityId}", 3600, function() use ($entityId) {
            try {
                $response = Http::withToken($this->token)
                    ->timeout($this->timeout)
                    ->get("{$this->baseUrl}/entities/{$entityId}");

                if ($response->successful()) {
                    return $response->json('data');
                }

                return null;
            } catch (\Exception $e) {
                Log::error("DASMe API: Exception fetching entity {$entityId}", [
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Get user's auctions from DASMe
     */
    public function getUserAuctions(int $userId, array $filters = []): array
    {
        $cacheKey = "dasm_auctions_user_{$userId}_" . md5(json_encode($filters));

        return Cache::remember($cacheKey, 300, function() use ($userId, $filters) {
            try {
                $response = Http::withToken($this->token)
                    ->timeout($this->timeout)
                    ->get("{$this->baseUrl}/auctions", array_merge($filters, [
                        'user_id' => $userId,
                    ]));

                if ($response->successful()) {
                    return $response->json('data', []);
                }

                return [];
            } catch (\Exception $e) {
                Log::error("DASMe API: Exception fetching auctions for user {$userId}", [
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Get user's bidding profile (for targeting)
     */
    public function getUserBiddingProfile(int $userId): array
    {
        return Cache::remember("dasm_bidding_profile_{$userId}", 1800, function() use ($userId) {
            $auctions = $this->getUserAuctions($userId, ['status' => 'completed']);

            $bids = [];
            foreach ($auctions as $auction) {
                if (isset($auction['bids']) && is_array($auction['bids'])) {
                    $bids = array_merge($bids, $auction['bids']);
                }
            }

            return [
                'total_bids' => count($bids),
                'won_count' => count(array_filter($auctions, fn($a) => ($a['winner_id'] ?? null) === $userId)),
                'lost_count' => count($auctions) - count(array_filter($auctions, fn($a) => ($a['winner_id'] ?? null) === $userId)),
                'favorite_brands' => $this->extractFavoriteBrands($auctions),
                'price_range' => $this->extractPriceRange($bids),
                'win_rate' => $this->calculateWinRate($auctions, $userId),
                'total_spent' => array_sum(array_column($auctions, 'final_price')),
            ];
        });
    }

    /**
     * Send conversion webhook to DASMe
     */
    public function sendConversionWebhook(array $conversionData): bool
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/webhooks/ad-conversions", [
                    'event' => 'ad_conversion',
                    'data' => $conversionData,
                    'timestamp' => now()->toIso8601String(),
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning("DASMe API: Failed to send conversion webhook", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error("DASMe API: Exception sending conversion webhook", [
                'error' => $e->getMessage(),
                'data' => $conversionData,
            ]);

            return false;
        }
    }

    /**
     * Clear cache for a specific resource
     */
    public function clearCache(string $type, int $id): void
    {
        Cache::forget("dasm_{$type}_{$id}");
    }

    /**
     * Check if DASMe API is healthy
     */
    public function healthCheck(): bool
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(5)
                ->get("{$this->baseUrl}/health");

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    // Helper methods

    protected function extractFavoriteBrands(array $auctions): array
    {
        $brands = [];
        foreach ($auctions as $auction) {
            if (isset($auction['car']['brand'])) {
                $brand = $auction['car']['brand'];
                $brands[$brand] = ($brands[$brand] ?? 0) + 1;
            }
        }

        arsort($brands);
        return array_slice(array_keys($brands), 0, 5); // Top 5
    }

    protected function extractPriceRange(array $bids): array
    {
        if (empty($bids)) {
            return [0, 0];
        }

        $amounts = array_filter(array_column($bids, 'amount'));
        
        if (empty($amounts)) {
            return [0, 0];
        }

        return [min($amounts), max($amounts)];
    }

    protected function calculateWinRate(array $auctions, int $userId): float
    {
        if (empty($auctions)) {
            return 0.0;
        }

        $won = count(array_filter($auctions, fn($a) => ($a['winner_id'] ?? null) === $userId));
        return round(($won / count($auctions)) * 100, 2);
    }
}

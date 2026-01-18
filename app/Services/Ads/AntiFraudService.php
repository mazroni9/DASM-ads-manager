<?php

namespace App\Services\Ads;

use App\Models\AdEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AntiFraudService
{
    protected array $settings;

    public function __construct()
    {
        $this->settings = config('ads.anti_fraud', [
            'click_cooldown_seconds' => 20,
            'max_clicks_per_session_per_creative' => 10,
            'max_clicks_per_5min_per_session' => 20,
            'min_viewport_seconds' => 1,
            'min_visible_ratio' => 0.5,
        ]);
    }

    /**
     * Validate event (impression/click/lead) for fraud
     * 
     * @param string $eventType
     * @param array $context
     * @param int $creativeId
     * @return array
     */
    public function validateEvent(string $eventType, array $context, int $creativeId): array
    {
        if ($eventType === 'impression') {
            return $this->validateImpression($context);
        }

        if ($eventType === 'click') {
            return $this->validateClick($context, $creativeId);
        }

        if ($eventType === 'lead') {
            return $this->validateLead($context);
        }

        return [
            'valid' => true,
            'reason' => null,
        ];
    }

    /**
     * Validate impression
     */
    protected function validateImpression(array $context): array
    {
        // Check viewport visibility
        $viewportSeconds = $context['viewport_seconds'] ?? 0;
        $visibleRatio = $context['visible_ratio'] ?? 0;

        if ($viewportSeconds < $this->settings['min_viewport_seconds']) {
            return [
                'valid' => false,
                'reason' => 'Insufficient viewport time',
            ];
        }

        if ($visibleRatio < $this->settings['min_visible_ratio']) {
            return [
                'valid' => false,
                'reason' => 'Insufficient visible ratio',
            ];
        }

        return [
            'valid' => true,
            'reason' => null,
        ];
    }

    /**
     * Validate click
     */
    protected function validateClick(array $context, int $creativeId): array
    {
        $sessionId = $context['session_id'] ?? null;
        $userId = $context['user_id'] ?? null;
        $ip = $context['ip'] ?? null;
        $userAgent = $context['user_agent'] ?? null;

        // 1. Check cooldown (same session, same creative)
        $cooldownKey = "click_cooldown_{$sessionId}_{$creativeId}";
        $lastClick = Cache::get($cooldownKey);

        if ($lastClick) {
            $lastClickTime = Carbon::parse($lastClick);
            $cooldownSeconds = $this->settings['click_cooldown_seconds'];
            
            if ($lastClickTime->addSeconds($cooldownSeconds)->isFuture()) {
                return [
                    'valid' => false,
                    'reason' => 'Click cooldown not expired',
                ];
            }
        }

        // Set cooldown
        Cache::put($cooldownKey, now()->toIso8601String(), $cooldownSeconds);

        // 2. Check max clicks per session per creative
        $sessionKey = "clicks_session_{$sessionId}_{$creativeId}";
        $clickCount = Cache::get($sessionKey, 0);

        if ($clickCount >= $this->settings['max_clicks_per_session_per_creative']) {
            return [
                'valid' => false,
                'reason' => 'Max clicks per session exceeded',
            ];
        }

        // Increment counter
        Cache::put($sessionKey, $clickCount + 1, 3600); // 1 hour TTL

        // 3. Check max clicks per 5 minutes per session
        $timeWindowKey = "clicks_5min_{$sessionId}";
        $recentClicks = Cache::get($timeWindowKey, []);

        // Filter clicks within last 5 minutes
        $fiveMinutesAgo = now()->subMinutes(5);
        $recentClicks = array_filter($recentClicks, function ($clickTime) use ($fiveMinutesAgo) {
            return Carbon::parse($clickTime)->isAfter($fiveMinutesAgo);
        });

        if (count($recentClicks) >= $this->settings['max_clicks_per_5min_per_session']) {
            return [
                'valid' => false,
                'reason' => 'Max clicks per 5 minutes exceeded',
            ];
        }

        // Add current click
        $recentClicks[] = now()->toIso8601String();
        Cache::put($timeWindowKey, $recentClicks, 300); // 5 minutes TTL

        // 4. Check suspicious user agent
        if ($userAgent && $this->isSuspiciousUserAgent($userAgent)) {
            return [
                'valid' => false,
                'reason' => 'Suspicious user agent',
            ];
        }

        // 5. Check duplicate clicks (same IP + UA + creative within short time)
        if ($ip && $userAgent) {
            $fingerprintKey = "click_fp_{$ip}_{$userAgent}_{$creativeId}";
            $lastClickFingerprint = Cache::get($fingerprintKey);

            if ($lastClickFingerprint) {
                $lastClickTime = Carbon::parse($lastClickFingerprint);
                
                // Same fingerprint clicking same creative within 10 seconds = suspicious
                if ($lastClickTime->addSeconds(10)->isFuture()) {
                    return [
                        'valid' => false,
                        'reason' => 'Duplicate click pattern',
                    ];
                }
            }

            Cache::put($fingerprintKey, now()->toIso8601String(), 60);
        }

        return [
            'valid' => true,
            'reason' => null,
        ];
    }

    /**
     * Validate lead
     */
    protected function validateLead(array $context): array
    {
        // Lead validation can be more complex
        // For now, just check basic requirements
        
        $userId = $context['user_id'] ?? null;
        
        if (!$userId) {
            return [
                'valid' => false,
                'reason' => 'User ID required for lead',
            ];
        }

        return [
            'valid' => true,
            'reason' => null,
        ];
    }

    /**
     * Check if user agent is suspicious
     */
    protected function isSuspiciousUserAgent(string $userAgent): bool
    {
        $suspiciousPatterns = [
            'bot',
            'crawler',
            'spider',
            'scraper',
            'curl',
            'wget',
            'python-requests',
            'java/',
            'postman',
        ];

        $userAgentLower = strtolower($userAgent);

        foreach ($suspiciousPatterns as $pattern) {
            if (str_contains($userAgentLower, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mark event as fraudulent
     */
    public function markAsFraudulent(int $eventId, string $reason): void
    {
        $event = AdEvent::find($eventId);

        if ($event && $event->is_valid) {
            $event->update([
                'is_valid' => false,
                'invalid_reason' => $reason,
            ]);

            Log::warning('Event marked as fraudulent', [
                'event_id' => $eventId,
                'reason' => $reason,
            ]);
        }
    }
}

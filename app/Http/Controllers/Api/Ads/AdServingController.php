<?php

namespace App\Http\Controllers\Api\Ads;

use App\Http\Controllers\Controller;
use App\Services\Ads\AdServingService;
use App\Services\Ads\TrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdServingController extends Controller
{
    protected AdServingService $servingService;
    protected TrackingService $trackingService;

    public function __construct(
        AdServingService $servingService,
        TrackingService $trackingService
    ) {
        $this->servingService = $servingService;
        $this->trackingService = $trackingService;
    }

    /**
     * Serve ads for display
     * 
     * GET /api/ads/serve
     */
    public function serve(Request $request): JsonResponse
    {
        $request->validate([
            'placement' => 'required|in:home,search_listings,car_details,auction_room,live_stream_overlay',
            'session_id' => 'required|string',
            'position' => 'sometimes|string',
            'city' => 'sometimes|string',
            'region' => 'sometimes|string',
            'user_type' => 'sometimes|in:guest,registered,verified_buyer,dealer,company_user',
            'search_make' => 'sometimes|string',
            'search_model' => 'sometimes|string',
            'price_range' => 'sometimes|array|size:2',
            'current_car_id' => 'sometimes|integer',
            'auction_stage' => 'sometimes|string',
        ]);

        $context = [
            'placement' => $request->input('placement'),
            'position' => $request->input('position'),
            'session_id' => $request->input('session_id'),
            'city' => $request->input('city'),
            'region' => $request->input('region'),
            'user_type' => $request->input('user_type', 'guest'),
            'user_id' => $request->user()?->id,
            'search_make' => $request->input('search_make'),
            'search_model' => $request->input('search_model'),
            'price_range' => $request->input('price_range'),
            'current_car_id' => $request->input('current_car_id'),
            'auction_stage' => $request->input('auction_stage'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        // Get limit based on placement
        $limit = $this->getLimitForPlacement($request->input('placement'));

        $ads = $this->servingService->serveAds($context, $limit);

        return response()->json([
            'data' => $ads,
        ]);
    }

    /**
     * Track ad event (impression/click/lead)
     * 
     * POST /api/ads/track
     */
    public function track(Request $request): JsonResponse
    {
        $request->validate([
            'tracking_token' => 'required|string',
            'event_type' => 'required|in:impression,click,lead',
            'session_id' => 'required|string',
            'context' => 'sometimes|array',
        ]);

        $context = array_merge(
            $request->input('context', []),
            [
                'session_id' => $request->input('session_id'),
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'viewport_seconds' => $request->input('context.viewport_seconds', 0),
                'visible_ratio' => $request->input('context.visible_ratio', 0),
            ]
        );

        $result = $this->trackingService->track(
            $request->input('tracking_token'),
            $request->input('event_type'),
            $context
        );

        if (!$result['ok']) {
            return response()->json([
                'ok' => false,
                'error' => $result['error'] ?? $result['reason'] ?? 'Tracking failed',
                'is_valid' => false,
            ], 400);
        }

        return response()->json($result);
    }

    /**
     * Get limit for placement
     */
    protected function getLimitForPlacement(string $placement): int
    {
        $slots = config("ads.slots.{$placement}", []);

        if (isset($slots['top_3'])) {
            return $slots['top_3'];
        }

        if (isset($slots['similar_sponsored'])) {
            return $slots['similar_sponsored'];
        }

        return 3; // Default
    }
}

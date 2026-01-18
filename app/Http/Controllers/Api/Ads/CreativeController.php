<?php

namespace App\Http\Controllers\Api\Ads;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\AdCreative;
use App\Services\DasmIntegration\DasmApiClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CreativeController extends Controller
{
    protected DasmApiClient $dasmClient;

    public function __construct(DasmApiClient $dasmClient)
    {
        $this->dasmClient = $dasmClient;
    }

    /**
     * List creatives
     * 
     * GET /api/ads/creatives
     */
    public function index(Request $request): JsonResponse
    {
        $query = AdCreative::with(['campaign', 'car']);

        if ($request->has('campaign_id')) {
            $query->where('campaign_id', $request->input('campaign_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $creatives = $query->latest()->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => $creatives->items(),
            'meta' => [
                'current_page' => $creatives->currentPage(),
                'total' => $creatives->total(),
            ],
        ]);
    }

    /**
     * Add creative to campaign
     * 
     * POST /api/ads/campaigns/{id}/creatives
     */
    public function store(Request $request, int $campaignId): JsonResponse
    {
        $campaign = AdCampaign::findOrFail($campaignId);
        
        $this->authorize('update', $campaign);

        $validated = $request->validate([
            'car_id' => 'required|integer',
            'headline' => 'sometimes|string|max:80|nullable',
            'subtitle' => 'sometimes|string|max:120|nullable',
            'cta' => 'sometimes|in:view_details,bid_now,contact,whatsapp',
        ]);

        // Validate car exists in DASMe and belongs to entity
        $car = $this->dasmClient->getCar($validated['car_id']);
        
        if (!$car) {
            return response()->json([
                'error' => 'Car not found in DASMe platform',
            ], 404);
        }

        // Check ownership
        $entityId = $campaign->adAccount->entity_id;
        if (isset($car['owner_entity_id']) && $car['owner_entity_id'] !== $entityId) {
            return response()->json([
                'error' => 'Car does not belong to this entity',
            ], 403);
        }

        // Check if already exists in campaign
        $existing = AdCreative::where('campaign_id', $campaignId)
            ->where('car_id', $validated['car_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'error' => 'Car already added to this campaign',
            ], 422);
        }

        $creative = AdCreative::create([
            'campaign_id' => $campaignId,
            'car_id' => $validated['car_id'],
            'headline' => $validated['headline'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'cta' => $validated['cta'] ?? 'view_details',
            'status' => 'pending',
            'created_by_user_id' => $request->user()->id,
        ]);

        return response()->json([
            'data' => $creative->load('car'),
        ], 201);
    }

    /**
     * Update creative
     * 
     * PATCH /api/ads/creatives/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $creative = AdCreative::findOrFail($id);
        
        $this->authorize('update', $creative->campaign);

        // If approved, changing will revert to pending
        $willRevertToPending = $creative->status === 'approved';

        $validated = $request->validate([
            'headline' => 'sometimes|string|max:80|nullable',
            'subtitle' => 'sometimes|string|max:120|nullable',
            'cta' => 'sometimes|in:view_details,bid_now,contact,whatsapp',
        ]);

        $creative->update($validated);

        if ($willRevertToPending) {
            $creative->update(['status' => 'pending']);
        }

        return response()->json([
            'data' => $creative->fresh(),
        ]);
    }

    /**
     * Get creative details
     * 
     * GET /api/ads/creatives/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $creative = AdCreative::with(['campaign', 'car'])->findOrFail($id);

        $this->authorize('view', $creative->campaign);

        return response()->json([
            'data' => $creative,
        ]);
    }
}

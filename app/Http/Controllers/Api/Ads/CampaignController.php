<?php

namespace App\Http\Controllers\Api\Ads;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\AdAccount;
use App\Services\Ads\TargetingValidatorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    protected TargetingValidatorService $targetingValidator;

    public function __construct(TargetingValidatorService $targetingValidator)
    {
        $this->targetingValidator = $targetingValidator;
    }

    /**
     * List campaigns
     * 
     * GET /api/ads/campaigns
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get user's ad account
        $adAccount = AdAccount::whereHas('entity', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->first();

        if (!$adAccount) {
            return response()->json([
                'data' => [],
                'meta' => ['total' => 0],
            ]);
        }

        $query = AdCampaign::where('ad_account_id', $adAccount->id)
            ->with(['creatives.car']);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }

        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->input('to'));
        }

        $campaigns = $query->latest()->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => $campaigns->items(),
            'meta' => [
                'current_page' => $campaigns->currentPage(),
                'total' => $campaigns->total(),
                'per_page' => $campaigns->perPage(),
            ],
        ]);
    }

    /**
     * Get campaign details
     * 
     * GET /api/ads/campaigns/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $campaign = AdCampaign::with(['creatives.car', 'adAccount.entity'])
            ->findOrFail($id);

        // Check ownership
        $this->authorize('view', $campaign);

        return response()->json([
            'data' => $campaign,
        ]);
    }

    /**
     * Create campaign
     * 
     * POST /api/ads/campaigns
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'objective' => 'required|in:views,traffic,leads',
            'pricing_model' => 'required|in:CPC,CPM',
            'daily_budget' => 'required|numeric|min:' . config('ads.min_daily_budget', 10),
            'total_budget' => 'sometimes|numeric|nullable',
            'start_at' => 'sometimes|date|nullable',
            'end_at' => 'sometimes|date|nullable|after_or_equal:start_at',
            'targeting' => 'required|array',
        ]);

        // Validate targeting
        $targetingValidation = $this->targetingValidator->validate($validated['targeting']);
        
        if (!$targetingValidation['valid']) {
            return response()->json([
                'error' => 'Invalid targeting',
                'errors' => $targetingValidation['errors'],
            ], 422);
        }

        // Normalize targeting
        $validated['targeting'] = $this->targetingValidator->normalize($validated['targeting']);

        // Get user's ad account
        $user = $request->user();
        $adAccount = $this->getOrCreateAdAccount($user);

        $campaign = AdCampaign::create([
            'ad_account_id' => $adAccount->id,
            'name' => $validated['name'],
            'objective' => $validated['objective'],
            'pricing_model' => $validated['pricing_model'],
            'daily_budget' => $validated['daily_budget'],
            'total_budget' => $validated['total_budget'] ?? null,
            'start_at' => isset($validated['start_at']) ? $validated['start_at'] : null,
            'end_at' => isset($validated['end_at']) ? $validated['end_at'] : null,
            'targeting' => $validated['targeting'],
            'status' => 'draft',
            'created_by_user_id' => $user->id,
        ]);

        return response()->json([
            'data' => $campaign,
        ], 201);
    }

    /**
     * Update campaign
     * 
     * PATCH /api/ads/campaigns/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $campaign = AdCampaign::findOrFail($id);
        
        $this->authorize('update', $campaign);

        // Can't update if submitted or active (business rule)
        if (in_array($campaign->status, ['pending', 'active'])) {
            return response()->json([
                'error' => 'Cannot update campaign in ' . $campaign->status . ' status',
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'daily_budget' => 'sometimes|numeric|min:' . config('ads.min_daily_budget', 10),
            'total_budget' => 'sometimes|numeric|nullable',
            'start_at' => 'sometimes|date|nullable',
            'end_at' => 'sometimes|date|nullable|after_or_equal:start_at',
            'targeting' => 'sometimes|array',
        ]);

        // Validate targeting if provided
        if (isset($validated['targeting'])) {
            $targetingValidation = $this->targetingValidator->validate($validated['targeting']);
            
            if (!$targetingValidation['valid']) {
                return response()->json([
                    'error' => 'Invalid targeting',
                    'errors' => $targetingValidation['errors'],
                ], 422);
            }

            $validated['targeting'] = $this->targetingValidator->normalize($validated['targeting']);
        }

        $campaign->update($validated);

        return response()->json([
            'data' => $campaign->fresh(),
        ]);
    }

    /**
     * Submit campaign for approval
     * 
     * POST /api/ads/campaigns/{id}/submit
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        $campaign = AdCampaign::findOrFail($id);
        
        $this->authorize('update', $campaign);

        if ($campaign->status !== 'draft') {
            return response()->json([
                'error' => 'Only draft campaigns can be submitted',
            ], 422);
        }

        // Check if campaign has creatives
        if ($campaign->creatives()->count() === 0) {
            return response()->json([
                'error' => 'Campaign must have at least one creative before submission',
            ], 422);
        }

        $campaign->update(['status' => 'pending']);

        return response()->json([
            'data' => $campaign->fresh(),
        ]);
    }

    /**
     * Pause campaign
     * 
     * POST /api/ads/campaigns/{id}/pause
     */
    public function pause(Request $request, int $id): JsonResponse
    {
        $campaign = AdCampaign::findOrFail($id);
        
        $this->authorize('update', $campaign);

        if ($campaign->status !== 'active') {
            return response()->json([
                'error' => 'Only active campaigns can be paused',
            ], 422);
        }

        $campaign->update(['status' => 'paused']);

        return response()->json([
            'data' => $campaign->fresh(),
        ]);
    }

    /**
     * Resume campaign
     * 
     * POST /api/ads/campaigns/{id}/resume
     */
    public function resume(Request $request, int $id): JsonResponse
    {
        $campaign = AdCampaign::findOrFail($id);
        
        $this->authorize('update', $campaign);

        if (!in_array($campaign->status, ['paused', 'budget_exhausted'])) {
            return response()->json([
                'error' => 'Campaign cannot be resumed from ' . $campaign->status . ' status',
            ], 422);
        }

        // Check budget
        $wallet = $campaign->adAccount->wallet;
        if (!$wallet || $wallet->balance_available < $campaign->daily_budget * 0.1) {
            return response()->json([
                'error' => 'Insufficient balance to resume campaign',
            ], 422);
        }

        $campaign->update(['status' => 'active']);

        return response()->json([
            'data' => $campaign->fresh(),
        ]);
    }

    /**
     * Get or create ad account for user
     */
    protected function getOrCreateAdAccount($user): AdAccount
    {
        // This is simplified - in real app, get entity from user
        // For now, assume user has an entity
        $entity = \App\Models\Entity::where('user_id', $user->id)->first();

        if (!$entity) {
            // Create entity
            $entity = \App\Models\Entity::create([
                'type' => 'individual',
                'user_id' => $user->id,
                'name' => $user->name ?? 'User ' . $user->id,
                'status' => 'active',
            ]);
        }

        $adAccount = AdAccount::where('entity_id', $entity->id)->first();

        if (!$adAccount) {
            $adAccount = AdAccount::create([
                'entity_id' => $entity->id,
                'currency' => 'SAR',
                'status' => 'active',
                'created_by_user_id' => $user->id,
            ]);

            // Create wallet
            \App\Models\AdWallet::create([
                'ad_account_id' => $adAccount->id,
            ]);
        }

        return $adAccount;
    }
}

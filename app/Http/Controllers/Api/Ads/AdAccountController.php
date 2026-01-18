<?php

namespace App\Http\Controllers\Api\Ads;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdAccountController extends Controller
{
    /**
     * Get current user's ad account
     * 
     * GET /api/ads/account
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $adAccount = AdAccount::whereHas('entity', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with(['wallet', 'entity'])->first();

        if (!$adAccount) {
            return response()->json([
                'error' => 'Ad account not found',
            ], 404);
        }

        return response()->json([
            'data' => [
                'account' => $adAccount,
                'wallet' => $adAccount->wallet,
                'entity' => $adAccount->entity,
            ],
        ]);
    }
}

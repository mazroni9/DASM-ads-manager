<?php

namespace App\Http\Controllers\Api\Ads;

use App\Http\Controllers\Controller;
use App\Models\AdAccount;
use App\Models\AdWalletTransaction;
use App\Services\Ads\BillingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    protected BillingService $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * Get wallet transactions
     * 
     * GET /api/ads/wallet/transactions
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();

        $adAccount = AdAccount::whereHas('entity', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with('wallet')->first();

        if (!$adAccount || !$adAccount->wallet) {
            return response()->json([
                'data' => [],
                'meta' => ['total' => 0],
            ]);
        }

        $query = AdWalletTransaction::where('ad_wallet_id', $adAccount->wallet->id);

        // Filters
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }

        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->input('to'));
        }

        if ($request->has('campaign_id')) {
            $query->where('related_campaign_id', $request->input('campaign_id'));
        }

        $transactions = $query->latest('created_at')->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Topup wallet
     * 
     * POST /api/ads/wallet/topup
     */
    public function topup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:10',
            'provider' => 'required|in:moyasar,hyperpay',
        ]);

        $user = $request->user();

        $adAccount = AdAccount::whereHas('entity', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with('wallet')->first();

        if (!$adAccount || !$adAccount->wallet) {
            return response()->json([
                'error' => 'Wallet not found',
            ], 404);
        }

        // TODO: Integrate with payment gateway (Moyasar/HyperPay)
        // For now, return mock response
        $paymentId = null; // Will be set after payment gateway integration

        $result = $this->billingService->topup(
            $adAccount->wallet,
            $validated['amount'],
            $paymentId
        );

        if (!$result['success']) {
            return response()->json([
                'error' => $result['error'],
            ], 422);
        }

        return response()->json([
            'data' => [
                'payment_id' => $paymentId,
                'payment_url' => null, // Will be from payment gateway
                'status' => 'pending', // Will be updated via webhook
                'amount' => $validated['amount'],
            ],
        ]);
    }
}

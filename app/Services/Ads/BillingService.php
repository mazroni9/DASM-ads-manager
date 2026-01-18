<?php

namespace App\Services\Ads;

use App\Models\AdCampaign;
use App\Models\AdWallet;
use App\Models\AdWalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingService
{
    /**
     * Charge wallet for ad event
     * 
     * @param AdCampaign $campaign
     * @param float $amount
     * @param string $eventType
     * @param int|null $creativeId
     * @param int|null $eventId
     * @return array
     */
    public function charge(AdCampaign $campaign, float $amount, string $eventType, ?int $creativeId = null, ?int $eventId = null): array
    {
        try {
            DB::beginTransaction();

            // Get wallet
            $wallet = $campaign->adAccount->wallet;

            if (!$wallet) {
                DB::rollBack();
                return [
                    'success' => false,
                    'error' => 'Wallet not found',
                ];
            }

            // Check budget
            $dailyRemaining = $campaign->daily_budget - $campaign->daily_spent;
            
            if ($dailyRemaining < $amount) {
                DB::rollBack();
                
                // Mark campaign as budget exhausted
                if ($campaign->status === 'active') {
                    $campaign->update(['status' => 'budget_exhausted']);
                }

                return [
                    'success' => false,
                    'error' => 'Daily budget exhausted',
                    'daily_remaining' => $dailyRemaining,
                ];
            }

            // Check wallet balance
            if ($wallet->balance_available < $amount) {
                DB::rollBack();

                // Mark campaign as budget exhausted
                if ($campaign->status === 'active') {
                    $campaign->update(['status' => 'budget_exhausted']);
                }

                return [
                    'success' => false,
                    'error' => 'Insufficient balance',
                    'balance_available' => $wallet->balance_available,
                ];
            }

            // Deduct from wallet
            $wallet->balance_available -= $amount;
            $wallet->lifetime_spent += $amount;
            $wallet->save();

            // Update campaign daily spent
            $campaign->daily_spent += $amount;
            $campaign->save();

            // Create transaction
            $transaction = AdWalletTransaction::create([
                'ad_wallet_id' => $wallet->id,
                'type' => 'spend',
                'amount' => -$amount, // Negative for deduction
                'currency' => config('ads.currency', 'SAR'),
                'related_campaign_id' => $campaign->id,
                'related_event_id' => $eventId,
                'meta' => [
                    'event_type' => $eventType,
                    'creative_id' => $creativeId,
                ],
                'created_at' => now(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'amount' => $amount,
                'transaction_id' => $transaction->id,
                'balance_remaining' => $wallet->balance_available,
                'daily_spent' => $campaign->daily_spent,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Billing charge failed', [
                'campaign_id' => $campaign->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Charge failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Refund a charge
     */
    public function refund(int $transactionId, string $reason = 'fraud_detection'): array
    {
        try {
            DB::beginTransaction();

            $transaction = AdWalletTransaction::find($transactionId);

            if (!$transaction || $transaction->type !== 'spend') {
                DB::rollBack();
                return [
                    'success' => false,
                    'error' => 'Transaction not found or invalid',
                ];
            }

            $wallet = $transaction->wallet;
            $refundAmount = abs($transaction->amount);

            // Refund to wallet
            $wallet->balance_available += $refundAmount;
            $wallet->lifetime_spent -= $refundAmount;
            $wallet->save();

            // Update campaign daily spent
            if ($transaction->related_campaign_id) {
                $campaign = AdCampaign::find($transaction->related_campaign_id);
                if ($campaign) {
                    $campaign->daily_spent -= $refundAmount;
                    $campaign->save();
                }
            }

            // Create refund transaction
            $refundTransaction = AdWalletTransaction::create([
                'ad_wallet_id' => $wallet->id,
                'type' => 'refund',
                'amount' => $refundAmount,
                'currency' => $transaction->currency,
                'related_campaign_id' => $transaction->related_campaign_id,
                'related_event_id' => $transaction->related_event_id,
                'meta' => [
                    'original_transaction_id' => $transactionId,
                    'reason' => $reason,
                ],
                'created_at' => now(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'amount' => $refundAmount,
                'refund_transaction_id' => $refundTransaction->id,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Billing refund failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Refund failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Add topup to wallet
     */
    public function topup(AdWallet $wallet, float $amount, ?int $paymentId = null): array
    {
        try {
            DB::beginTransaction();

            // Add to wallet
            $wallet->balance_available += $amount;
            $wallet->save();

            // Create transaction
            $transaction = AdWalletTransaction::create([
                'ad_wallet_id' => $wallet->id,
                'type' => 'topup',
                'amount' => $amount,
                'currency' => config('ads.currency', 'SAR'),
                'related_payment_id' => $paymentId,
                'created_at' => now(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'amount' => $amount,
                'transaction_id' => $transaction->id,
                'balance_available' => $wallet->balance_available,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Billing topup failed', [
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Topup failed: ' . $e->getMessage(),
            ];
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_account_id',
        'balance_available',
        'balance_hold',
        'lifetime_spent',
    ];

    protected $casts = [
        'balance_available' => 'decimal:2',
        'balance_hold' => 'decimal:2',
        'lifetime_spent' => 'decimal:2',
    ];

    /**
     * Get the ad account that owns this wallet
     */
    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }

    /**
     * Get all transactions for this wallet
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(AdWalletTransaction::class)->orderBy('created_at', 'desc');
    }

    /**
     * Check if wallet has sufficient balance
     */
    public function hasBalance(float $amount): bool
    {
        return $this->balance_available >= $amount;
    }

    /**
     * Get available balance for spending
     */
    public function getAvailableBalance(): float
    {
        return (float) $this->balance_available;
    }
}

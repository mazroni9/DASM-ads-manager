<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdWalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_wallet_id',
        'type',
        'amount',
        'currency',
        'related_payment_id',
        'related_campaign_id',
        'related_event_id',
        'meta',
        'created_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    protected static function booted()
    {
        static::creating(function ($transaction) {
            $transaction->created_at = now();
        });
    }

    /**
     * Get the wallet that owns this transaction
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(AdWallet::class, 'ad_wallet_id');
    }

    /**
     * Get the related campaign (if any)
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'related_campaign_id');
    }

    /**
     * Get the related event (if any)
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(AdEvent::class, 'related_event_id');
    }
}

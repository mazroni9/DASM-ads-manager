<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'ad_account_id',
        'name',
        'objective',
        'pricing_model',
        'status',
        'daily_budget',
        'total_budget',
        'daily_spent',
        'start_at',
        'end_at',
        'targeting',
        'approved_at',
        'approved_by_user_id',
        'rejected_reason',
        'created_by_user_id',
    ];

    protected $casts = [
        'daily_budget' => 'decimal:2',
        'total_budget' => 'decimal:2',
        'daily_spent' => 'decimal:2',
        'targeting' => 'array',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the ad account that owns this campaign
     */
    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }

    /**
     * Get all creatives for this campaign
     */
    public function creatives(): HasMany
    {
        return $this->hasMany(AdCreative::class, 'campaign_id');
    }

    /**
     * Get all events for this campaign
     */
    public function events(): HasMany
    {
        return $this->hasMany(AdEvent::class, 'campaign_id');
    }

    /**
     * Get daily stats for this campaign
     */
    public function dailyStats(): HasMany
    {
        return $this->hasMany(AdDailyStat::class, 'campaign_id');
    }

    /**
     * Check if campaign is currently active
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();
        
        if ($this->start_at && $now->lt($this->start_at)) {
            return false;
        }

        if ($this->end_at && $now->gt($this->end_at)) {
            return false;
        }

        return true;
    }

    /**
     * Check if campaign has remaining daily budget
     */
    public function hasRemainingDailyBudget(float $amount = 0): bool
    {
        return ($this->daily_spent + $amount) <= $this->daily_budget;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdCreative extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'car_id',
        'creative_type',
        'headline',
        'subtitle',
        'cta',
        'status',
        'rejected_reason',
        'created_by_user_id',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Get the campaign that owns this creative
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'campaign_id');
    }

    /**
     * Get the car being promoted by this creative
     */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Get all events for this creative
     */
    public function events(): HasMany
    {
        return $this->hasMany(AdEvent::class, 'creative_id');
    }

    /**
     * Check if creative is approved and ready to serve
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'creative_id',
        'placement',
        'position',
        'event_type',
        'cost_charged',
        'currency',
        'user_id',
        'session_id_hash',
        'ip_hash',
        'user_agent_hash',
        'served_at',
        'created_at',
        'context',
        'is_valid',
        'invalid_reason',
    ];

    protected $casts = [
        'cost_charged' => 'decimal:4',
        'context' => 'array',
        'is_valid' => 'boolean',
        'served_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    protected static function booted()
    {
        static::creating(function ($event) {
            if (!$event->created_at) {
                $event->created_at = now();
            }
        });
    }

    /**
     * Get the campaign for this event
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class);
    }

    /**
     * Get the creative for this event
     */
    public function creative(): BelongsTo
    {
        return $this->belongsTo(AdCreative::class);
    }

    /**
     * Get the user (if any) for this event
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdDailyStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'campaign_id',
        'creative_id',
        'placement',
        'impressions',
        'clicks',
        'leads',
        'spend',
    ];

    protected $casts = [
        'date' => 'date',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'leads' => 'integer',
        'spend' => 'decimal:2',
    ];

    /**
     * Get the campaign for this stat
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class);
    }

    /**
     * Get the creative for this stat (if breakdown by creative)
     */
    public function creative(): BelongsTo
    {
        return $this->belongsTo(AdCreative::class);
    }

    /**
     * Calculate CTR (Click-Through Rate)
     */
    public function getCtrAttribute(): float
    {
        if ($this->impressions === 0) {
            return 0.0;
        }

        return ($this->clicks / $this->impressions) * 100;
    }

    /**
     * Calculate CPC (Cost Per Click)
     */
    public function getCpcAttribute(): float
    {
        if ($this->clicks === 0) {
            return 0.0;
        }

        return (float) ($this->spend / $this->clicks);
    }

    /**
     * Calculate CPM (Cost Per 1000 Impressions)
     */
    public function getCpmAttribute(): float
    {
        if ($this->impressions === 0) {
            return 0.0;
        }

        return (float) (($this->spend / $this->impressions) * 1000);
    }
}

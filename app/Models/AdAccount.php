<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AdAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_id',
        'currency',
        'status',
        'created_by_user_id',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Get the entity that owns this ad account
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * Get the wallet for this account
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(AdWallet::class);
    }

    /**
     * Get all campaigns for this account
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class);
    }

    /**
     * Get the user who created this account
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Entity extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'user_id',
        'name',
        'commercial_number',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Get the user associated with this entity (if individual)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the ad account for this entity
     */
    public function adAccount(): HasOne
    {
        return $this->hasOne(AdAccount::class);
    }

    /**
     * Get all cars owned by this entity
     */
    public function cars()
    {
        return $this->hasMany(Car::class, 'owner_entity_id');
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AdCampaign;

class AdCampaignPolicy
{
    /**
     * Determine if the user can view the campaign.
     */
    public function view(User $user, AdCampaign $campaign): bool
    {
        return $campaign->adAccount->entity->user_id === $user->id;
    }

    /**
     * Determine if the user can update the campaign.
     */
    public function update(User $user, AdCampaign $campaign): bool
    {
        return $campaign->adAccount->entity->user_id === $user->id;
    }

    /**
     * Determine if the user can delete the campaign.
     */
    public function delete(User $user, AdCampaign $campaign): bool
    {
        return $campaign->adAccount->entity->user_id === $user->id;
    }
}

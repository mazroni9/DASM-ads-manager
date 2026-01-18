<?php

namespace App\Services\Ads;

use Illuminate\Support\Facades\Validator;

class TargetingValidatorService
{
    /**
     * Validate targeting JSON according to Targeting Schema v1
     */
    public function validate(array $targeting): array
    {
        $validator = Validator::make($targeting, $this->getRules());

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        // Additional custom validations
        $customErrors = $this->validateCustomRules($targeting);

        if (!empty($customErrors)) {
            return [
                'valid' => false,
                'errors' => $customErrors,
            ];
        }

        return [
            'valid' => true,
            'normalized' => $this->normalize($targeting),
        ];
    }

    /**
     * Normalize targeting by applying defaults
     */
    public function normalize(array $targeting): array
    {
        // Ensure version
        if (!isset($targeting['version'])) {
            $targeting['version'] = 1;
        }

        // Default placements
        if (!isset($targeting['placements']['include']) || empty($targeting['placements']['include'])) {
            $targeting['placements']['include'] = ['search_listings', 'car_details'];
        }

        // Default audience
        if (!isset($targeting['audience']['user_types']) || empty($targeting['audience']['user_types'])) {
            $targeting['audience']['user_types'] = ['registered', 'verified_buyer'];
        }

        // Default schedule
        if (!isset($targeting['schedule'])) {
            $targeting['schedule'] = [];
        }

        if (!isset($targeting['schedule']['pacing'])) {
            $targeting['schedule']['pacing'] = 'smooth';
        }

        if (!isset($targeting['schedule']['frequency_cap'])) {
            $targeting['schedule']['frequency_cap'] = [
                'impressions_per_user_per_day' => 5,
                'clicks_per_user_per_day' => 2,
            ];
        }

        if (!isset($targeting['schedule']['timezone'])) {
            $targeting['schedule']['timezone'] = 'Asia/Riyadh';
        }

        return $targeting;
    }

    /**
     * Get validation rules for targeting JSON
     */
    protected function getRules(): array
    {
        return [
            'version' => 'required|integer|in:1',
            'geo' => 'sometimes|array',
            'geo.include_cities' => 'sometimes|array',
            'geo.exclude_cities' => 'sometimes|array',
            'geo.include_regions' => 'sometimes|array',
            'geo.radius_km' => 'sometimes|integer|min:1|max:300',
            'geo.center' => 'sometimes|array',
            'geo.center.lat' => 'required_with:geo.center|numeric',
            'geo.center.lng' => 'required_with:geo.center|numeric',
            
            'inventory' => 'sometimes|array',
            'inventory.makes' => 'sometimes|array',
            'inventory.models' => 'sometimes|array',
            'inventory.year_min' => 'sometimes|integer|min:1980|max:'.(date('Y') + 1),
            'inventory.year_max' => 'sometimes|integer|min:1980|max:'.(date('Y') + 1),
            'inventory.price_min' => 'sometimes|numeric|min:0',
            'inventory.price_max' => 'sometimes|numeric|min:0',
            'inventory.mileage_min' => 'sometimes|numeric|min:0',
            'inventory.mileage_max' => 'sometimes|numeric|min:0',
            'inventory.body_types' => 'sometimes|array',
            'inventory.body_types.*' => 'in:Sedan,SUV,Truck,Van,Coupe,Hatchback,Other',
            'inventory.fuel_types' => 'sometimes|array',
            'inventory.fuel_types.*' => 'in:Gasoline,Diesel,Hybrid,Electric,Other',
            'inventory.transmission' => 'sometimes|array',
            'inventory.transmission.*' => 'in:Auto,Manual,CVT,Other',
            'inventory.condition' => 'sometimes|array',
            'inventory.condition.*' => 'in:New,Used,Salvage,TotalLoss,Repossession,Fleet',
            'inventory.tags' => 'sometimes|array',
            
            'auction_context' => 'sometimes|array',
            'auction_context.listing_type' => 'sometimes|array',
            'auction_context.listing_type.*' => 'in:fixed,auction',
            'auction_context.auction_stage' => 'sometimes|array',
            'auction_context.auction_stage.*' => 'in:live,instant,late,silent,none',
            'auction_context.car_status' => 'sometimes|array',
            'auction_context.car_status.*' => 'in:active,pending_inspection,ready_to_bid,in_auction',
            
            'audience' => 'sometimes|array',
            'audience.user_types' => 'sometimes|array',
            'audience.user_types.*' => 'in:guest,registered,verified_buyer,dealer,company_user',
            'audience.exclude_user_types' => 'sometimes|array',
            'audience.exclude_user_types.*' => 'in:guest,registered,verified_buyer,dealer,company_user',
            'audience.language' => 'sometimes|array',
            'audience.language.*' => 'in:ar,en',
            'audience.device' => 'sometimes|array',
            'audience.device.*' => 'in:web,mobile',
            'audience.buyer_intent_score_min' => 'sometimes|integer|min:0|max:100',
            
            'placements' => 'sometimes|array',
            'placements.include' => 'sometimes|array',
            'placements.include.*' => 'in:home,search_listings,car_details,auction_room,live_stream_overlay',
            'placements.exclude' => 'sometimes|array',
            'placements.exclude.*' => 'in:home,search_listings,car_details,auction_room,live_stream_overlay',
            'placements.positions' => 'sometimes|array',
            
            'schedule' => 'sometimes|array',
            'schedule.timezone' => 'sometimes|string',
            'schedule.start_at' => 'sometimes|date',
            'schedule.end_at' => 'sometimes|date|after_or_equal:schedule.start_at',
            'schedule.days_of_week' => 'sometimes|array',
            'schedule.days_of_week.*' => 'integer|min:0|max:6',
            'schedule.hours' => 'sometimes|array',
            'schedule.hours.from' => 'sometimes|integer|min:0|max:23',
            'schedule.hours.to' => 'sometimes|integer|min:0|max:23',
            'schedule.pacing' => 'sometimes|in:smooth,asap',
            'schedule.frequency_cap' => 'sometimes|array',
            'schedule.frequency_cap.impressions_per_user_per_day' => 'sometimes|integer|min:0|max:50',
            'schedule.frequency_cap.clicks_per_user_per_day' => 'sometimes|integer|min:0|max:20',
        ];
    }

    /**
     * Validate custom business rules
     */
    protected function validateCustomRules(array $targeting): array
    {
        $errors = [];

        // Validate price range
        if (isset($targeting['inventory']['price_min']) && isset($targeting['inventory']['price_max'])) {
            if ($targeting['inventory']['price_min'] > $targeting['inventory']['price_max']) {
                $errors['inventory.price_min'] = ['price_min must be less than or equal to price_max'];
            }
        }

        // Validate year range
        if (isset($targeting['inventory']['year_min']) && isset($targeting['inventory']['year_max'])) {
            if ($targeting['inventory']['year_min'] > $targeting['inventory']['year_max']) {
                $errors['inventory.year_min'] = ['year_min must be less than or equal to year_max'];
            }
        }

        // Validate mileage range
        if (isset($targeting['inventory']['mileage_min']) && isset($targeting['inventory']['mileage_max'])) {
            if ($targeting['inventory']['mileage_min'] > $targeting['inventory']['mileage_max']) {
                $errors['inventory.mileage_min'] = ['mileage_min must be less than or equal to mileage_max'];
            }
        }

        // Validate hours range
        if (isset($targeting['schedule']['hours']['from']) && isset($targeting['schedule']['hours']['to'])) {
            if ($targeting['schedule']['hours']['from'] > $targeting['schedule']['hours']['to']) {
                $errors['schedule.hours.from'] = ['hours.from must be less than or equal to hours.to'];
            }
        }

        return $errors;
    }

    /**
     * Check if targeting matches context
     */
    public function matches(array $targeting, array $context): bool
    {
        $normalized = $this->normalize($targeting);

        // Check placement
        if (isset($context['placement'])) {
            $include = $normalized['placements']['include'] ?? [];
            $exclude = $normalized['placements']['exclude'] ?? [];
            
            if (in_array($context['placement'], $exclude)) {
                return false;
            }
            
            if (!empty($include) && !in_array($context['placement'], $include)) {
                return false;
            }
        }

        // Check geo
        if (isset($normalized['geo']) && isset($context['city'])) {
            $includeCities = $normalized['geo']['include_cities'] ?? [];
            $excludeCities = $normalized['geo']['exclude_cities'] ?? [];
            
            if (in_array($context['city'], $excludeCities)) {
                return false;
            }
            
            if (!empty($includeCities) && !in_array($context['city'], $includeCities)) {
                // Check region
                $includeRegions = $normalized['geo']['include_regions'] ?? [];
                if (empty($includeRegions) || !isset($context['region'])) {
                    return false;
                }
                if (!in_array($context['region'], $includeRegions)) {
                    return false;
                }
            }
        }

        // Check audience
        if (isset($normalized['audience']) && isset($context['user_type'])) {
            $userTypes = $normalized['audience']['user_types'] ?? [];
            $excludeTypes = $normalized['audience']['exclude_user_types'] ?? [];
            
            if (in_array($context['user_type'], $excludeTypes)) {
                return false;
            }
            
            if (!empty($userTypes) && !in_array($context['user_type'], $userTypes)) {
                return false;
            }
        }

        // Check schedule (time)
        if (isset($normalized['schedule'])) {
            $schedule = $normalized['schedule'];
            $now = now()->setTimezone($schedule['timezone'] ?? 'Asia/Riyadh');
            
            if (isset($schedule['start_at']) && $now->lt($schedule['start_at'])) {
                return false;
            }
            
            if (isset($schedule['end_at']) && $now->gt($schedule['end_at'])) {
                return false;
            }
            
            if (isset($schedule['days_of_week'])) {
                $dayOfWeek = $now->dayOfWeek;
                if (!in_array($dayOfWeek, $schedule['days_of_week'])) {
                    return false;
                }
            }
            
            if (isset($schedule['hours'])) {
                $hour = (int) $now->format('H');
                $from = $schedule['hours']['from'] ?? 0;
                $to = $schedule['hours']['to'] ?? 23;
                
                if ($hour < $from || $hour > $to) {
                    return false;
                }
            }
        }

        return true;
    }
}

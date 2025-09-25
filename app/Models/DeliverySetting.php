<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliverySetting extends Model
{
    use HasFactory;

    protected $table = 'delivery_settings';

    protected $fillable = [
        'delivery_enabled',
        'delivery_fee',
        'free_delivery_threshold',
        'delivery_radius_km',
        'estimated_delivery_time_min',
        'monday_hours',
        'tuesday_hours',
        'wednesday_hours',
        'thursday_hours',
        'friday_hours',
        'saturday_hours',
        'sunday_hours',
        'delivery_zones',
        'special_instructions',
        'contact_phone',
        'contact_email',
        'store_address'
    ];

    protected $casts = [
        'delivery_enabled' => 'boolean',
        'delivery_fee' => 'decimal:2',
        'free_delivery_threshold' => 'decimal:2',
        'delivery_radius_km' => 'decimal:1',
        'estimated_delivery_time_min' => 'integer'
    ];

    /**
     * Get delivery zones as array
     */
    public function getDeliveryZonesAttribute($value)
    {
        if (!$value) {
            return [
                [
                    'name' => 'Zone 1 - City Center',
                    'postcodes' => ['3000', '3001', '3002', '3003'],
                    'fee' => 8.00
                ],
                [
                    'name' => 'Zone 2 - Suburbs',
                    'postcodes' => ['3004', '3005', '3006', '3007', '3008'],
                    'fee' => 12.00
                ],
                [
                    'name' => 'Zone 3 - Outer Areas',
                    'postcodes' => ['3009', '3010', '3011', '3012'],
                    'fee' => 15.00
                ]
            ];
        }

        return json_decode($value, true);
    }

    /**
     * Set delivery zones as JSON
     */
    public function setDeliveryZonesAttribute($value)
    {
        $this->attributes['delivery_zones'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Check if delivery is available for a postcode
     */
    public function isPostcodeInDeliveryZone(string $postcode): bool
    {
        $zones = $this->delivery_zones;

        foreach ($zones as $zone) {
            if (in_array($postcode, $zone['postcodes'] ?? [])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get delivery fee for a specific postcode
     */
    public function getDeliveryFeeForPostcode(string $postcode): float
    {
        $zones = $this->delivery_zones;

        foreach ($zones as $zone) {
            if (in_array($postcode, $zone['postcodes'] ?? [])) {
                return (float) ($zone['fee'] ?? $this->delivery_fee);
            }
        }

        return (float) $this->delivery_fee;
    }

    /**
     * Get delivery hours for a specific day
     */
    public function getHoursForDay(string $day): string
    {
        $field = strtolower($day) . '_hours';
        return $this->$field ?? 'closed';
    }

    /**
     * Check if delivery is available today
     */
    public function isOpenToday(): bool
    {
        $today = strtolower(now()->format('l'));
        $hours = $this->getHoursForDay($today);

        return $this->delivery_enabled && $hours !== 'closed';
    }

    /**
     * Get all delivery hours as array
     */
    public function getAllHours(): array
    {
        return [
            'monday' => $this->monday_hours ?? '9:00-17:00',
            'tuesday' => $this->tuesday_hours ?? '9:00-17:00',
            'wednesday' => $this->wednesday_hours ?? '9:00-17:00',
            'thursday' => $this->thursday_hours ?? '9:00-17:00',
            'friday' => $this->friday_hours ?? '9:00-17:00',
            'saturday' => $this->saturday_hours ?? '9:00-17:00',
            'sunday' => $this->sunday_hours ?? 'closed',
        ];
    }

    /**
     * Calculate final delivery fee considering free delivery threshold
     */
    public function calculateDeliveryFee(string $postcode, float $orderTotal): array
    {
        if (!$this->delivery_enabled) {
            return [
                'available' => false,
                'fee' => 0,
                'reason' => 'Delivery service is disabled'
            ];
        }

        if (!$this->isPostcodeInDeliveryZone($postcode)) {
            return [
                'available' => false,
                'fee' => 0,
                'reason' => 'Delivery not available to this postcode'
            ];
        }

        $baseFee = $this->getDeliveryFeeForPostcode($postcode);
        $finalFee = $baseFee;

        if ($orderTotal >= $this->free_delivery_threshold) {
            $finalFee = 0;
        }

        return [
            'available' => true,
            'base_fee' => $baseFee,
            'final_fee' => $finalFee,
            'free_delivery_applied' => $finalFee == 0 && $orderTotal >= $this->free_delivery_threshold,
            'free_delivery_threshold' => $this->free_delivery_threshold,
            'amount_for_free_delivery' => max(0, $this->free_delivery_threshold - $orderTotal)
        ];
    }
}
<?php

namespace App\Helpers;

class ShippingHelper
{
    public static function calculateShippingCost(string $postcode, float $totalWeightKg): float
    {
        // Australian postcode validation
        if (!self::isValidAustralianPostcode($postcode)) {
            throw new \InvalidArgumentException('Invalid Australian postcode');
        }

        // Base shipping cost
        $baseCost = 15.00;

        // Weight-based pricing (per kg)
        $weightCost = $totalWeightKg * 2.50;

        // Distance-based pricing based on postcode zones
        $distanceCost = self::getDistanceCostByPostcode($postcode);

        $totalCost = $baseCost + $weightCost + $distanceCost;

        // Minimum shipping cost
        return max($totalCost, 10.00);
    }

    public static function isValidAustralianPostcode(string $postcode): bool
    {
        // Australian postcodes are 4 digits
        return preg_match('/^\d{4}$/', $postcode);
    }

    public static function getDeliveryZone(string $postcode): string
    {
        $code = (int) $postcode;

        // Major city zones (lower shipping cost)
        if (self::isMajorCityPostcode($code)) {
            return 'metro';
        }

        // Regional zones
        if (self::isRegionalPostcode($code)) {
            return 'regional';
        }

        // Remote zones (higher shipping cost)
        return 'remote';
    }

    protected static function getDistanceCostByPostcode(string $postcode): float
    {
        $zone = self::getDeliveryZone($postcode);

        return match ($zone) {
            'metro' => 5.00,
            'regional' => 12.00,
            'remote' => 25.00,
            default => 15.00,
        };
    }

    protected static function isMajorCityPostcode(int $postcode): bool
    {
        // Sydney metro area
        if ($postcode >= 1000 && $postcode <= 2234) return true;
        if ($postcode >= 2555 && $postcode <= 2574) return true;
        if ($postcode >= 2745 && $postcode <= 2786) return true;

        // Melbourne metro area
        if ($postcode >= 3000 && $postcode <= 3207) return true;
        if ($postcode >= 3335 && $postcode <= 3341) return true;
        if ($postcode >= 3400 && $postcode <= 3444) return true;
        if ($postcode >= 3750 && $postcode <= 3810) return true;

        // Brisbane metro area
        if ($postcode >= 4000 && $postcode <= 4207) return true;
        if ($postcode >= 4300 && $postcode <= 4381) return true;

        // Adelaide metro area
        if ($postcode >= 5000 && $postcode <= 5199) return true;

        // Perth metro area
        if ($postcode >= 6000 && $postcode <= 6214) return true;

        return false;
    }

    protected static function isRegionalPostcode(int $postcode): bool
    {
        // NSW regional
        if ($postcode >= 2300 && $postcode <= 2554) return true;
        if ($postcode >= 2575 && $postcode <= 2739) return true;
        if ($postcode >= 2787 && $postcode <= 2898) return true;

        // VIC regional
        if ($postcode >= 3208 && $postcode <= 3334) return true;
        if ($postcode >= 3342 && $postcode <= 3399) return true;
        if ($postcode >= 3445 && $postcode <= 3749) return true;
        if ($postcode >= 3811 && $postcode <= 3996) return true;

        // QLD regional
        if ($postcode >= 4208 && $postcode <= 4299) return true;
        if ($postcode >= 4382 && $postcode <= 4999) return true;

        // SA regional
        if ($postcode >= 5200 && $postcode <= 5799) return true;

        // WA regional
        if ($postcode >= 6215 && $postcode <= 6999) return true;

        // TAS (all considered regional except Hobart)
        if ($postcode >= 7000 && $postcode <= 7999) return true;

        // ACT
        if ($postcode >= 2600 && $postcode <= 2699) return true;

        // NT
        if ($postcode >= 800 && $postcode <= 899) return true;

        return false;
    }

    public static function getEstimatedDeliveryDays(string $postcode): array
    {
        $zone = self::getDeliveryZone($postcode);

        return match ($zone) {
            'metro' => [1, 2], // 1-2 business days
            'regional' => [2, 4], // 2-4 business days
            'remote' => [5, 10], // 5-10 business days
            default => [3, 5],
        };
    }
}
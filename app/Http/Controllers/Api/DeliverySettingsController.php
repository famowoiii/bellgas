<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliverySetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class DeliverySettingsController extends Controller
{
    /**
     * Get delivery settings
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $settings = Cache::remember('delivery_settings', 300, function () {
            return DeliverySetting::first() ?? new DeliverySetting();
        });

        return response()->json([
            'success' => true,
            'data' => [
                'delivery_enabled' => $settings->delivery_enabled ?? true,
                'delivery_fee' => $settings->delivery_fee ?? 10.00,
                'free_delivery_threshold' => $settings->free_delivery_threshold ?? 100.00,
                'delivery_radius_km' => $settings->delivery_radius_km ?? 15,
                'estimated_delivery_time_min' => $settings->estimated_delivery_time_min ?? 60,
                'delivery_hours' => [
                    'monday' => $settings->monday_hours ?? '9:00-17:00',
                    'tuesday' => $settings->tuesday_hours ?? '9:00-17:00',
                    'wednesday' => $settings->wednesday_hours ?? '9:00-17:00',
                    'thursday' => $settings->thursday_hours ?? '9:00-17:00',
                    'friday' => $settings->friday_hours ?? '9:00-17:00',
                    'saturday' => $settings->saturday_hours ?? '9:00-17:00',
                    'sunday' => $settings->sunday_hours ?? 'closed',
                ],
                'delivery_zones' => $this->getDeliveryZones($settings),
                'special_delivery_instructions' => $settings->special_instructions ?? '',
                'contact_info' => [
                    'phone' => $settings->contact_phone ?? '',
                    'email' => $settings->contact_email ?? '',
                    'address' => $settings->store_address ?? ''
                ]
            ]
        ]);
    }

    /**
     * Update delivery settings
     */
    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!in_array($user->role, ['ADMIN', 'MERCHANT'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'delivery_enabled' => 'boolean',
            'delivery_fee' => 'numeric|min:0|max:999.99',
            'free_delivery_threshold' => 'numeric|min:0|max:9999.99',
            'delivery_radius_km' => 'numeric|min:1|max:100',
            'estimated_delivery_time_min' => 'integer|min:15|max:480',
            'delivery_hours' => 'array',
            'delivery_hours.monday' => 'string|max:50',
            'delivery_hours.tuesday' => 'string|max:50',
            'delivery_hours.wednesday' => 'string|max:50',
            'delivery_hours.thursday' => 'string|max:50',
            'delivery_hours.friday' => 'string|max:50',
            'delivery_hours.saturday' => 'string|max:50',
            'delivery_hours.sunday' => 'string|max:50',
            'delivery_zones' => 'array',
            'delivery_zones.*.name' => 'string|max:100',
            'delivery_zones.*.postcodes' => 'array',
            'delivery_zones.*.postcodes.*' => 'string|max:10',
            'delivery_zones.*.fee' => 'numeric|min:0|max:999.99',
            'special_instructions' => 'string|max:1000',
            'contact_phone' => 'string|max:20',
            'contact_email' => 'email|max:255',
            'store_address' => 'string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $settings = DeliverySetting::first();
            if (!$settings) {
                $settings = new DeliverySetting();
            }

            // Update basic settings
            $settings->delivery_enabled = $request->get('delivery_enabled', $settings->delivery_enabled);
            $settings->delivery_fee = $request->get('delivery_fee', $settings->delivery_fee);
            $settings->free_delivery_threshold = $request->get('free_delivery_threshold', $settings->free_delivery_threshold);
            $settings->delivery_radius_km = $request->get('delivery_radius_km', $settings->delivery_radius_km);
            $settings->estimated_delivery_time_min = $request->get('estimated_delivery_time_min', $settings->estimated_delivery_time_min);
            $settings->special_instructions = $request->get('special_instructions', $settings->special_instructions);
            $settings->contact_phone = $request->get('contact_phone', $settings->contact_phone);
            $settings->contact_email = $request->get('contact_email', $settings->contact_email);
            $settings->store_address = $request->get('store_address', $settings->store_address);

            // Update delivery hours
            if ($request->has('delivery_hours')) {
                $hours = $request->delivery_hours;
                $settings->monday_hours = $hours['monday'] ?? $settings->monday_hours;
                $settings->tuesday_hours = $hours['tuesday'] ?? $settings->tuesday_hours;
                $settings->wednesday_hours = $hours['wednesday'] ?? $settings->wednesday_hours;
                $settings->thursday_hours = $hours['thursday'] ?? $settings->thursday_hours;
                $settings->friday_hours = $hours['friday'] ?? $settings->friday_hours;
                $settings->saturday_hours = $hours['saturday'] ?? $settings->saturday_hours;
                $settings->sunday_hours = $hours['sunday'] ?? $settings->sunday_hours;
            }

            // Update delivery zones
            if ($request->has('delivery_zones')) {
                $settings->delivery_zones = json_encode($request->delivery_zones);
            }

            $settings->save();

            // Clear cache
            Cache::forget('delivery_settings');

            return response()->json([
                'success' => true,
                'message' => 'Delivery settings updated successfully',
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update delivery settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get delivery zones for public use
     */
    public function getZones(): JsonResponse
    {
        $settings = Cache::remember('delivery_settings', 300, function () {
            return DeliverySetting::first();
        });

        $zones = $this->getDeliveryZones($settings);

        return response()->json([
            'success' => true,
            'data' => [
                'zones' => $zones,
                'delivery_enabled' => $settings->delivery_enabled ?? true,
                'base_delivery_fee' => $settings->delivery_fee ?? 10.00,
                'free_delivery_threshold' => $settings->free_delivery_threshold ?? 100.00,
                'delivery_radius_km' => $settings->delivery_radius_km ?? 15,
                'estimated_delivery_time_min' => $settings->estimated_delivery_time_min ?? 60
            ]
        ]);
    }

    /**
     * Check if delivery is available to specific postcode
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'postcode' => 'required|string|max:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $postcode = $request->postcode;
        $settings = Cache::remember('delivery_settings', 300, function () {
            return DeliverySetting::first();
        });

        if (!$settings || !$settings->delivery_enabled) {
            return response()->json([
                'success' => true,
                'data' => [
                    'available' => false,
                    'reason' => 'Delivery service is currently disabled',
                    'delivery_fee' => 0,
                    'estimated_time_min' => 0
                ]
            ]);
        }

        $zones = $this->getDeliveryZones($settings);
        $deliveryFee = $settings->delivery_fee ?? 10.00;
        $estimatedTime = $settings->estimated_delivery_time_min ?? 60;

        // Check if postcode is in any delivery zone
        foreach ($zones as $zone) {
            if (in_array($postcode, $zone['postcodes'])) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'available' => true,
                        'zone_name' => $zone['name'],
                        'delivery_fee' => $zone['fee'] ?? $deliveryFee,
                        'estimated_time_min' => $estimatedTime,
                        'free_delivery_threshold' => $settings->free_delivery_threshold ?? 100.00
                    ]
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'available' => false,
                'reason' => 'Delivery not available to this postcode',
                'delivery_fee' => 0,
                'estimated_time_min' => 0,
                'suggested_zones' => array_column($zones, 'name')
            ]
        ]);
    }

    /**
     * Get delivery hours for today
     */
    public function getTodayHours(): JsonResponse
    {
        $settings = Cache::remember('delivery_settings', 300, function () {
            return DeliverySetting::first();
        });

        $today = strtolower(now()->format('l')); // monday, tuesday, etc.
        $hoursField = $today . '_hours';
        $todayHours = $settings->$hoursField ?? 'closed';

        $allHours = [
            'monday' => $settings->monday_hours ?? '9:00-17:00',
            'tuesday' => $settings->tuesday_hours ?? '9:00-17:00',
            'wednesday' => $settings->wednesday_hours ?? '9:00-17:00',
            'thursday' => $settings->thursday_hours ?? '9:00-17:00',
            'friday' => $settings->friday_hours ?? '9:00-17:00',
            'saturday' => $settings->saturday_hours ?? '9:00-17:00',
            'sunday' => $settings->sunday_hours ?? 'closed',
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'today' => $today,
                'hours' => $todayHours,
                'is_open' => $todayHours !== 'closed',
                'all_hours' => $allHours,
                'delivery_enabled' => $settings->delivery_enabled ?? true
            ]
        ]);
    }

    /**
     * Calculate delivery fee for order
     */
    public function calculateFee(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'postcode' => 'required|string|max:10',
            'order_total' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $postcode = $request->postcode;
        $orderTotal = $request->order_total;

        $settings = Cache::remember('delivery_settings', 300, function () {
            return DeliverySetting::first();
        });

        if (!$settings || !$settings->delivery_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery service is currently disabled'
            ], 400);
        }

        $zones = $this->getDeliveryZones($settings);
        $baseDeliveryFee = $settings->delivery_fee ?? 10.00;
        $freeDeliveryThreshold = $settings->free_delivery_threshold ?? 100.00;

        // Find zone for postcode
        $deliveryFee = $baseDeliveryFee;
        $zoneName = 'Standard';

        foreach ($zones as $zone) {
            if (in_array($postcode, $zone['postcodes'])) {
                $deliveryFee = $zone['fee'] ?? $baseDeliveryFee;
                $zoneName = $zone['name'];
                break;
            }
        }

        // Check for free delivery
        $finalDeliveryFee = $deliveryFee;
        if ($orderTotal >= $freeDeliveryThreshold) {
            $finalDeliveryFee = 0;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'postcode' => $postcode,
                'zone_name' => $zoneName,
                'order_total' => $orderTotal,
                'base_delivery_fee' => $deliveryFee,
                'final_delivery_fee' => $finalDeliveryFee,
                'free_delivery_applied' => $finalDeliveryFee == 0 && $orderTotal >= $freeDeliveryThreshold,
                'free_delivery_threshold' => $freeDeliveryThreshold,
                'amount_for_free_delivery' => max(0, $freeDeliveryThreshold - $orderTotal)
            ]
        ]);
    }

    /**
     * Get delivery zones from settings
     */
    private function getDeliveryZones($settings): array
    {
        if (!$settings || !$settings->delivery_zones) {
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

        return json_decode($settings->delivery_zones, true);
    }
}
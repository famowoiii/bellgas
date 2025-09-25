<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Models\Address;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    public function index(): JsonResponse
    {
        $addresses = auth()->user()->addresses;

        return response()->json([
            'message' => 'Addresses retrieved successfully',
            'data' => $addresses->map(function ($address) {
                return [
                    'id' => $address->id,
                    'type' => $address->type,
                    'name' => $address->name,
                    'street_address' => $address->street_address,
                    'suburb' => $address->suburb,
                    'state' => $address->state,
                    'postcode' => $address->postcode,
                    'country' => $address->country,
                    'delivery_instructions' => $address->delivery_instructions,
                    'is_default' => $address->is_default,
                    'full_address' => $address->full_address,
                    'created_at' => $address->created_at,
                ];
            }),
        ]);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        // If this is set as default, unset other defaults
        if ($request->is_default) {
            auth()->user()->addresses()->update(['is_default' => false]);
        }

        $address = auth()->user()->addresses()->create($request->validated());

        return response()->json([
            'message' => 'Address created successfully',
            'data' => [
                'id' => $address->id,
                'type' => $address->type,
                'name' => $address->name,
                'street_address' => $address->street_address,
                'suburb' => $address->suburb,
                'state' => $address->state,
                'postcode' => $address->postcode,
                'country' => $address->country,
                'delivery_instructions' => $address->delivery_instructions,
                'is_default' => $address->is_default,
                'full_address' => $address->full_address,
                'created_at' => $address->created_at,
            ],
        ], 201);
    }

    public function show(Address $address): JsonResponse
    {
        // Check ownership
        if ($address->user_id !== auth('api')->user()->id) {
            return response()->json(['message' => 'Address not found'], 404);
        }

        return response()->json([
            'message' => 'Address retrieved successfully',
            'data' => [
                'id' => $address->id,
                'type' => $address->type,
                'name' => $address->name,
                'street_address' => $address->street_address,
                'suburb' => $address->suburb,
                'state' => $address->state,
                'postcode' => $address->postcode,
                'country' => $address->country,
                'delivery_instructions' => $address->delivery_instructions,
                'is_default' => $address->is_default,
                'full_address' => $address->full_address,
                'created_at' => $address->created_at,
            ],
        ]);
    }

    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        // Check ownership
        if ($address->user_id !== auth('api')->user()->id) {
            return response()->json(['message' => 'Address not found'], 404);
        }

        // If this is set as default, unset other defaults
        if ($request->is_default) {
            auth('api')->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($request->validated());

        return response()->json([
            'message' => 'Address updated successfully',
            'data' => [
                'id' => $address->id,
                'type' => $address->type,
                'name' => $address->name,
                'street_address' => $address->street_address,
                'suburb' => $address->suburb,
                'state' => $address->state,
                'postcode' => $address->postcode,
                'country' => $address->country,
                'delivery_instructions' => $address->delivery_instructions,
                'is_default' => $address->is_default,
                'full_address' => $address->full_address,
                'updated_at' => $address->updated_at,
            ],
        ]);
    }

    public function destroy(Address $address): JsonResponse
    {
        // Check ownership
        if ($address->user_id !== auth('api')->user()->id) {
            return response()->json(['message' => 'Address not found'], 404);
        }

        $address->delete();

        return response()->json([
            'message' => 'Address deleted successfully',
        ]);
    }
}

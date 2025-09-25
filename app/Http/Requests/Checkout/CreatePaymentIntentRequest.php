<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentIntentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $rules = [
            'fulfillment_method' => ['required', 'string', 'in:PICKUP,DELIVERY'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
        ];

        // Only require address for delivery orders
        if ($this->fulfillment_method === 'DELIVERY') {
            $rules['address_id'] = ['required', 'exists:addresses,id'];
        } else {
            $rules['address_id'] = ['nullable', 'exists:addresses,id'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'address_id.exists' => 'Selected address is not valid.',
            'fulfillment_method.in' => 'Fulfillment method must be PICKUP or DELIVERY.',
            'items.required' => 'At least one item is required.',
            'items.*.product_variant_id.exists' => 'One or more selected product variants are invalid.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.quantity.max' => 'Maximum quantity per item is 10.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Only validate address ownership if address_id is provided (for delivery orders)
            if ($this->address_id && auth()->user()->addresses()->where('id', $this->address_id)->doesntExist()) {
                $validator->errors()->add('address_id', 'You can only use your own addresses.');
            }
            
            // Validate delivery restriction for refill products
            if ($this->fulfillment_method === 'DELIVERY' && $this->items) {
                $refillItems = [];
                foreach ($this->items as $index => $item) {
                    $productVariant = \App\Models\ProductVariant::with('product')
                        ->find($item['product_variant_id'] ?? null);
                    
                    if ($productVariant && $productVariant->product->isRefill()) {
                        $refillItems[] = $productVariant->product->name . ' - ' . $productVariant->name;
                    }
                }
                
                if (!empty($refillItems)) {
                    $itemsList = implode(', ', $refillItems);
                    $validator->errors()->add(
                        'fulfillment_method', 
                        "Refill products can only be picked up at the store. Please select 'PICKUP' for: {$itemsList}"
                    );
                }
            }
        });
    }

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        \Log::error('Checkout validation failed', [
            'user_id' => auth()->id(),
            'errors' => $validator->errors()->toArray(),
            'request_data' => $this->all()
        ]);

        parent::failedValidation($validator);
    }
}

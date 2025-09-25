<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $address = $this->route('address');
        return auth()->check() && auth()->user()->addresses()->where('id', $address->id)->exists();
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', 'in:HOME,WORK,OTHER'],
            'name' => ['sometimes', 'string', 'max:255'],
            'street_address' => ['sometimes', 'string', 'max:500'],
            'suburb' => ['sometimes', 'string', 'max:255'],
            'state' => ['sometimes', 'string', 'max:100'],
            'postcode' => ['sometimes', 'string', 'regex:/^\d{4}$/'],
            'country' => ['sometimes', 'string', 'max:100'],
            'delivery_instructions' => ['nullable', 'string', 'max:1000'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'postcode.regex' => 'Postcode must be a valid 4-digit Australian postcode.',
            'type.in' => 'Address type must be HOME, WORK, or OTHER.',
        ];
    }
}

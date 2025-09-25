<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
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
        return [
            'type' => ['required', 'string', 'in:HOME,WORK,OTHER'],
            'name' => ['required', 'string', 'max:255'],
            'street_address' => ['required', 'string', 'max:500'],
            'suburb' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:100'],
            'postcode' => ['required', 'string', 'regex:/^\d{4}$/'],
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'country' => $this->country ?? 'Australia',
        ]);
    }
}

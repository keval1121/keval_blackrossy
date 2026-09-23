<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'mobile' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'address' => ['required', 'string', 'min:8', 'max:255'],
            'area' => ['nullable', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['required', 'string', 'max:80'],
            'pincode' => ['required', 'regex:/^[1-9][0-9]{5}$/'],
            'otp' => [setting('otp_enabled') ? 'required' : 'nullable', 'digits:6'],
            'notes' => ['nullable', 'string', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => 'Enter a valid 10-digit Indian mobile number.',
            'pincode.regex' => 'Enter a valid 6-digit pincode.',
        ];
    }
}

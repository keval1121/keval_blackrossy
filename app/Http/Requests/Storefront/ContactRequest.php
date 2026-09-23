<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:120'],
            'mobile' => ['nullable', 'regex:/^[6-9]\d{9}$/'],
            'subject' => ['nullable', 'string', 'max:140'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}

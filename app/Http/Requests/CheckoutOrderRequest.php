<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'shipping_company' => ['required', 'string', 'max:255'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_city' => ['required', 'string', 'max:100'],
            'shipping_contact' => ['required', 'string', 'max:30'],
            'payment_method' => ['required', 'in:bank_transfer,company_credit'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
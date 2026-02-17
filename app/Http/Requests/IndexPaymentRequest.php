<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string'],
            'household_id' => ['sometimes', 'string'],
            'payment_date_from' => ['sometimes', 'date_format:Y-m-d'],
            'payment_date_to' => ['sometimes', 'date_format:Y-m-d'],
            'per_page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_date_from.date_format' => 'The payment date from must match the format Y-m-d.',
            'payment_date_to.date_format' => 'The payment date to must match the format Y-m-d.',
        ];
    }
}

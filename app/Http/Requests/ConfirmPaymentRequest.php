<?php

namespace App\Http\Requests;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmPaymentRequest extends FormRequest
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
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', Rule::in([PaymentStatus::Paid->value, PaymentStatus::Failed->value])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_date.required' => 'The payment date field is required.',
            'payment_date.date_format' => 'The payment date must be in Y-m-d format.',
            'status.required' => 'The status field is required.',
            'status.in' => 'The status must be paid or failed.',
        ];
    }
}

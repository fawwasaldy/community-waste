<?php

namespace App\Http\Requests;

use App\Enums\WasteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePickupRequest extends FormRequest
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
            'household_id' => ['required', 'string', 'exists:households,_id'],
            'type' => ['required', 'string', Rule::in(array_column(WasteType::cases(), 'value'))],
            'safety_check' => ['required_if:type,' . WasteType::Electronic->value, 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'household_id.required' => 'The household ID field is required.',
            'household_id.exists' => 'The selected household does not exist.',
            'type.required' => 'The waste type field is required.',
            'type.in' => 'The selected waste type is invalid.',
            'safety_check.required_if' => 'The safety check field is required when the waste type is electronic.',
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Enums\WasteType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WasteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->_id,
            'household_id' => $this->household_id,
            'type' => $this->type,
            'pickup_date' => $this->pickup_date,
            'status' => $this->status,
            'safety_check' => $this->when($this->type === WasteType::Electronic, $this->safety_check),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

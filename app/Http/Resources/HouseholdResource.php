<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HouseholdResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->_id,
            'owner_name' => $this->owner_name,
            'address' => $this->address,
            'block' => $this->block,
            'no' => $this->no,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

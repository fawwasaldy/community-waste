<?php

namespace App\Models;

use App\Enums\WasteType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;

class WasteOrganic extends Waste
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(WasteType::Organic->value, function ($query) {
            $query->where('type', WasteType::Organic);
        });

        static::creating(function ($waste) {
            $waste->type = WasteType::Organic;
        });
    }

    /**
     * @throws ValidationException
     */
    public function validateSchedule(Carbon $pickupDate): void
    {
        parent::validateSchedule($pickupDate);

        if ($pickupDate->gt($this->created_at->copy()->addDays(3)->startOfDay())) {
            throw ValidationException::withMessages([
                'pickup_date' => ['Organic waste pickup must be scheduled within 3 days of creation.'],
            ]);
        }
    }
}

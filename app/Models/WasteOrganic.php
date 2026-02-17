<?php

namespace App\Models;

use App\Enums\WasteStatus;
use App\Enums\WasteType;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WasteOrganic extends Waste
{
    use HasFactory;

    protected $fillable = [
        'household_id',
        'type',
        'pickup_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'pickup_date' => 'date:Y-m-d',
            'status' => WasteStatus::class,
            'type' => WasteType::class,
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(WasteType::Organic->value, function ($query) {
            $query->where('type', WasteType::Organic);
        });

        static::creating(function ($waste) {
            $waste->type = WasteType::Organic;
        });
    }
}

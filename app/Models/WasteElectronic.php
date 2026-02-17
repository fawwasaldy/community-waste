<?php

namespace App\Models;

use App\Enums\WasteType;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WasteElectronic extends Waste
{
    use HasFactory;

    protected $fillable = [
        'household_id',
        'type',
        'pickup_date',
        'status',
        'safety_check',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'safety_check' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(WasteType::Electronic->value, function ($query) {
            $query->where('type', WasteType::Electronic);
        });

        static::creating(function ($waste) {
            $waste->type = WasteType::Electronic;
        });
    }
}

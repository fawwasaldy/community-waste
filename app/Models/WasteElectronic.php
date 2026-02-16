<?php

namespace App\Models;

use App\Enums\WasteStatus;

class WasteElectronic extends Waste
{
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
            'pickup_date' => 'date:Y-m-d',
            'status' => WasteStatus::class,
            'safety_check' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('electronic', function ($query) {
            $query->where('type', 'electronic');
        });

        static::creating(function ($waste) {
            $waste->type = 'electronic';
        });
    }
}

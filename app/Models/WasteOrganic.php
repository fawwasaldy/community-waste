<?php

namespace App\Models;

use App\Enums\WasteStatus;

class WasteOrganic extends Waste
{
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
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('organic', function ($query) {
            $query->where('type', 'organic');
        });

        static::creating(function ($waste) {
            $waste->type = 'organic';
        });
    }
}

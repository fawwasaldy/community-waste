<?php

namespace App\Models;

use App\Enums\WasteStatus;

class WastePaper extends Waste
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
        static::addGlobalScope('paper', function ($query) {
            $query->where('type', 'paper');
        });

        static::creating(function ($waste) {
            $waste->type = 'paper';
        });
    }
}

<?php

namespace App\Models;

use App\Enums\WasteType;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WastePlastic extends Waste
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(WasteType::Plastic->value, function ($query) {
            $query->where('type', WasteType::Plastic);
        });

        static::creating(function ($waste) {
            $waste->type = WasteType::Plastic;
        });
    }
}

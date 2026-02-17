<?php

namespace App\Models;

use App\Enums\WasteType;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
}

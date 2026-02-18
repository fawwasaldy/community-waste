<?php

namespace App\Models;

use App\Enums\WasteType;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WastePaper extends Waste
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(WasteType::Paper->value, function ($query) {
            $query->where('type', WasteType::Paper);
        });

        static::creating(function ($waste) {
            $waste->type = WasteType::Paper;
        });
    }
}

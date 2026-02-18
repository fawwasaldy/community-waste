<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Models\Concerns\SerializesDateToAppTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, SerializesDateToAppTimezone;

    protected $fillable = [
        'household_id',
        'amount',
        'payment_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date:Y-m-d',
            'status' => PaymentStatus::class,
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}

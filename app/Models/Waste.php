<?php

namespace App\Models;

use App\Enums\WasteStatus;
use App\Enums\WasteType;
use App\Models\Concerns\SerializesDateToAppTimezone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;

class Waste extends Model
{
    use HasFactory, SerializesDateToAppTimezone;

    protected $table = 'wastes';

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

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /**
     * @throws ValidationException
     */
    public function validateSchedule(Carbon $pickupDate): void
    {
        if ($pickupDate->lt($this->created_at->startOfDay())) {
            throw ValidationException::withMessages([
                'pickup_date' => ['The pickup date must be on or after the waste creation date.'],
            ]);
        }
    }

    public function getPaymentAmount(): int
    {
        return 50000;
    }
}

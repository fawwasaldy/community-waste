<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\HasMany;

class Household extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_name',
        'address',
        'block',
        'no',
    ];

    protected function casts(): array
    {
        return [
            'owner_name' => 'string',
            'address' => 'string',
            'block' => 'string',
            'no' => 'string',
        ];
    }

    public function wastes(): HasMany
    {
        return $this->hasMany(Waste::class);
    }

    protected function wasteOrganics(): HasMany
    {
        return $this->hasMany(WasteOrganic::class);
    }

    protected function wastePlastics(): HasMany
    {
        return $this->hasMany(WastePlastic::class);
    }

    protected function wastePapers(): HasMany
    {
        return $this->hasMany(WastePaper::class);
    }

    protected function wasteElectronics(): HasMany
    {
        return $this->hasMany(WasteElectronic::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function hasUnpaidPayments(): bool
    {
        return $this->payments()
            ->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::Failed->value])
            ->exists();
    }
}

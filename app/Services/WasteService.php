<?php

namespace App\Services;

use App\Enums\WasteStatus;
use App\Models\Household;
use App\Models\Waste;
use App\Repositories\WasteRepository;
use Illuminate\Validation\ValidationException;

class WasteService
{
    public function __construct(private WasteRepository $repository) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function createPickup(array $data): Waste
    {
        $household = Household::findOrFail($data['household_id']);

        if ($household->hasUnpaidPayments()) {
            throw ValidationException::withMessages([
                'household_id' => ['This household has unpaid payments and cannot request a pickup.'],
            ]);
        }

        $data['status'] = WasteStatus::Pending->value;

        return $this->repository->create($data['type'], $data);
    }
}

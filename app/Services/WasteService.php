<?php

namespace App\Services;

use App\Enums\WasteStatus;
use App\Models\Household;
use App\Models\Waste;
use App\Repositories\WasteRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class WasteService
{
    public function __construct(private WasteRepository $repository) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getPickups(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->query($filters)->paginate($perPage);
    }

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

    /**
     * @throws ValidationException
     * @throws ModelNotFoundException
     */
    public function schedulePickup(string $id, string $pickupDate): Waste
    {
        $waste = $this->repository->find($id);

        if (! $waste) {
            throw (new ModelNotFoundException)->setModel(Waste::class, $id);
        }

        if ($waste->status !== WasteStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => ['The pickup must be in pending status to be scheduled.'],
            ]);
        }

        $waste->validateSchedule(Carbon::parse($pickupDate));

        return $this->repository->updateSchedule($waste, $pickupDate);
    }
}

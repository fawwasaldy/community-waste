<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\WasteStatus;
use App\Models\Household;
use App\Models\Waste;
use App\Repositories\PaymentRepository;
use App\Repositories\WasteRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WasteService
{
    public function __construct(
        private WasteRepository $wasteRepository,
        private PaymentRepository $paymentRepository
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getPickups(array $filters, int $perPage = 10, bool $disablePagination = false): Collection|LengthAwarePaginator
    {
        $query = $this->wasteRepository->query($filters);

        if ($disablePagination) {
            return $query->get();
        }

        return $query->paginate($perPage);
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

        return $this->wasteRepository->create($data['type'], $data);
    }

    /**
     * @throws ValidationException
     * @throws ModelNotFoundException
     */
    public function schedulePickup(string $id, string $pickupDate): Waste
    {
        $waste = $this->wasteRepository->find($id);

        if (! $waste) {
            throw (new ModelNotFoundException)->setModel(Waste::class, $id);
        }

        if ($waste->status !== WasteStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => ['The pickup must be in pending status to be scheduled.'],
            ]);
        }

        $waste->validateSchedule(Carbon::parse($pickupDate));

        return $this->wasteRepository->update($waste, [
            'pickup_date' => $pickupDate,
            'status' => WasteStatus::Scheduled->value,
        ]);
    }

    /**
     * @throws ValidationException
     * @throws ModelNotFoundException
     */
    public function completePickup(string $id): Waste
    {
        $waste = $this->wasteRepository->find($id);

        if (! $waste) {
            throw (new ModelNotFoundException)->setModel(Waste::class, $id);
        }

        if ($waste->status !== WasteStatus::Scheduled) {
            throw ValidationException::withMessages([
                'status' => ['The pickup must be in scheduled status to be completed.'],
            ]);
        }

        return DB::transaction(function () use ($waste): Waste {
            $this->paymentRepository->create([
                'household_id' => $waste->household_id,
                'amount' => $waste->getPaymentAmount(),
                'payment_date' => null,
                'status' => PaymentStatus::Pending->value,
            ]);

            return $this->wasteRepository->update($waste, [
                'status' => WasteStatus::Completed->value,
            ]);
        });
    }

    /**
     * @throws ValidationException
     * @throws ModelNotFoundException
     */
    public function cancelPickup(string $id): Waste
    {
        $waste = $this->wasteRepository->find($id);

        if (! $waste) {
            throw (new ModelNotFoundException)->setModel(Waste::class, $id);
        }

        if ($waste->status !== WasteStatus::Scheduled) {
            throw ValidationException::withMessages([
                'status' => ['The pickup must be in scheduled status to be canceled.'],
            ]);
        }

        return $this->wasteRepository->update($waste, [
            'status' => WasteStatus::Canceled->value,
        ]);
    }
}

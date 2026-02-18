<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private PaymentRepository $paymentRepository) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getPayments(array $filters, int $perPage = 10, bool $disablePagination = false): Collection|LengthAwarePaginator
    {
        $query = $this->paymentRepository->query($filters);

        if ($disablePagination) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPayment(array $data): Payment
    {
        $data['payment_date'] = null;
        $data['status'] = PaymentStatus::Pending->value;

        return $this->paymentRepository->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     * @throws ModelNotFoundException
     */
    public function confirmPayment(string $id, array $data): Payment
    {
        $payment = $this->paymentRepository->find($id);

        if (! $payment) {
            throw (new ModelNotFoundException)->setModel(Payment::class, $id);
        }

        if ($payment->status !== PaymentStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => ['The payment must be in pending status to be confirmed.'],
            ]);
        }

        return $this->paymentRepository->updateConfirmation(
            $payment,
            $data['payment_date'],
            PaymentStatus::from($data['status']),
        );
    }
}

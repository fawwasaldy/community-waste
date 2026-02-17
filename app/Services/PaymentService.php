<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentService
{
    public function __construct(private PaymentRepository $paymentRepository) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getPayments(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->paymentRepository->query($filters)->paginate($perPage);
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
}

<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Repositories\PaymentRepository;

class PaymentService
{
    public function __construct(private PaymentRepository $paymentRepository) {}

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

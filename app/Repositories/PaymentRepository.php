<?php

namespace App\Repositories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;

class PaymentRepository
{
    public function find(string $id): ?Payment
    {
        /** @var Payment|null */
        return Payment::find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Payment $payment, array $data): Payment
    {
        $payment->update($data);

        return $payment->refresh();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(array $filters = []): Builder
    {
        $query = Payment::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['household_id'])) {
            $query->where('household_id', $filters['household_id']);
        }

        if (isset($filters['payment_date_from'])) {
            $query->where('payment_date', '>=', $filters['payment_date_from']);
        }

        if (isset($filters['payment_date_to'])) {
            $query->where('payment_date', '<=', $filters['payment_date_to']);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Payment
    {
        return Payment::create($data);
    }
}

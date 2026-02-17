<?php

namespace App\Repositories;

use App\Models\Payment;

class PaymentRepository
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Payment
    {
        return Payment::create($data);
    }
}

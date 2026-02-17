<?php

use App\Enums\PaymentStatus;
use App\Models\Household;
use App\Models\Payment;
use App\Models\User;
use App\Repositories\PaymentRepository;

beforeEach(function () {
    User::query()->delete();
    Household::query()->delete();
    Payment::query()->delete();
});

describe('create', function () {
    it('creates a payment with correct attributes', function () {
        $household = Household::factory()->create();
        $repository = new PaymentRepository;

        $payment = $repository->create([
            'household_id' => $household->_id,
            'amount' => 50000,
            'payment_date' => null,
            'status' => PaymentStatus::Pending->value,
        ]);

        expect($payment)->toBeInstanceOf(Payment::class)
            ->and($payment->household_id)->toBe($household->_id)
            ->and((int) $payment->amount)->toBe(50000)
            ->and($payment->status)->toBe(PaymentStatus::Pending)
            ->and($payment->payment_date)->toBeNull();
    });
});

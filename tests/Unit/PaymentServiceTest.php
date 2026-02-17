<?php

use App\Enums\PaymentStatus;
use App\Models\Household;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;

beforeEach(function () {
    User::query()->delete();
    Household::query()->delete();
    Payment::query()->delete();
});

describe('createPayment', function () {
    it('creates a payment with status pending and payment_date null', function () {
        $household = Household::factory()->create();
        $service = app(PaymentService::class);

        $payment = $service->createPayment([
            'household_id' => $household->_id,
            'amount' => 50000,
        ]);

        expect($payment)->toBeInstanceOf(Payment::class)
            ->and($payment->status)->toBe(PaymentStatus::Pending)
            ->and($payment->payment_date)->toBeNull();
    });

    it('stores the correct household_id and amount', function () {
        $household = Household::factory()->create();
        $service = app(PaymentService::class);

        $payment = $service->createPayment([
            'household_id' => $household->_id,
            'amount' => 75000,
        ]);

        expect($payment->household_id)->toBe($household->_id)
            ->and((int) $payment->amount)->toBe(75000);
    });
});

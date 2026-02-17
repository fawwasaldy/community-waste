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

describe('query', function () {
    it('returns all payments when no filters', function () {
        Payment::factory()->count(3)->create();

        $repository = new PaymentRepository;
        $results = $repository->query()->get();

        expect($results)->toHaveCount(3);
    });

    it('filters by status', function () {
        Payment::factory()->create(['status' => PaymentStatus::Pending->value]);
        Payment::factory()->paid()->create();

        $repository = new PaymentRepository;
        $results = $repository->query(['status' => 'pending'])->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->status->value)->toBe('pending');
    });

    it('filters by household_id', function () {
        $household = Household::factory()->create();
        Payment::factory()->create(['household_id' => $household->_id]);
        Payment::factory()->create();

        $repository = new PaymentRepository;
        $results = $repository->query(['household_id' => $household->_id])->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->household_id)->toBe($household->_id);
    });

    it('filters by payment_date_from', function () {
        Payment::factory()->create(['payment_date' => '2025-01-15']);
        Payment::factory()->create(['payment_date' => '2025-02-15']);

        $repository = new PaymentRepository;
        $results = $repository->query(['payment_date_from' => '2025-02-01'])->get();

        expect($results)->toHaveCount(1);
    });

    it('filters by payment_date_to', function () {
        Payment::factory()->create(['payment_date' => '2025-01-15']);
        Payment::factory()->create(['payment_date' => '2025-02-15']);

        $repository = new PaymentRepository;
        $results = $repository->query(['payment_date_to' => '2025-01-31'])->get();

        expect($results)->toHaveCount(1);
    });

    it('filters by payment_date range', function () {
        Payment::factory()->create(['payment_date' => '2025-01-10']);
        Payment::factory()->create(['payment_date' => '2025-01-20']);
        Payment::factory()->create(['payment_date' => '2025-02-15']);

        $repository = new PaymentRepository;
        $results = $repository->query([
            'payment_date_from' => '2025-01-15',
            'payment_date_to' => '2025-01-31',
        ])->get();

        expect($results)->toHaveCount(1);
    });
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

<?php

use App\Enums\PaymentStatus;
use App\Models\Household;
use App\Models\Payment;
use App\Models\User;
use App\Models\Waste;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    User::query()->delete();
    Household::query()->delete();
    Waste::query()->delete();
    Payment::query()->delete();
});

describe('getPayments', function () {
    it('returns paginated results', function () {
        Payment::factory()->count(3)->create();

        $service = app(PaymentService::class);
        $result = $service->getPayments([], 2);

        expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class)
            ->and($result->count())->toBe(2)
            ->and($result->total())->toBe(3);
    });

    it('returns all results as Collection when disablePagination is true', function () {
        Payment::factory()->count(5)->create();

        $service = app(PaymentService::class);
        $result = $service->getPayments([], 10, true);

        expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
            ->and($result)->toHaveCount(5);
    });

    it('passes filters through to repository', function () {
        Payment::factory()->create(['status' => PaymentStatus::Pending->value]);
        Payment::factory()->paid()->create();

        $service = app(PaymentService::class);
        $result = $service->getPayments(['status' => 'pending']);

        expect($result->total())->toBe(1);
    });
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

describe('confirmPayment', function () {
    it('confirms a pending payment as paid', function () {
        $payment = Payment::factory()->create(['payment_date' => null]);
        $service = app(PaymentService::class);

        $result = $service->confirmPayment($payment->_id, [
            'payment_date' => '2025-01-15',
            'status' => 'paid',
        ]);

        expect($result->status)->toBe(PaymentStatus::Paid)
            ->and($result->payment_date->format('Y-m-d'))->toBe('2025-01-15');
    });

    it('confirms a pending payment as failed', function () {
        $payment = Payment::factory()->create(['payment_date' => null]);
        $service = app(PaymentService::class);

        $result = $service->confirmPayment($payment->_id, [
            'payment_date' => '2025-02-10',
            'status' => 'failed',
        ]);

        expect($result->status)->toBe(PaymentStatus::Failed);
    });

    it('throws ValidationException when status is not pending', function () {
        $payment = Payment::factory()->paid()->create();
        $service = app(PaymentService::class);

        $service->confirmPayment($payment->_id, [
            'payment_date' => '2025-01-15',
            'status' => 'paid',
        ]);
    })->throws(ValidationException::class);

    it('throws ModelNotFoundException when payment not found', function () {
        $service = app(PaymentService::class);

        $service->confirmPayment('nonexistent-id', [
            'payment_date' => '2025-01-15',
            'status' => 'paid',
        ]);
    })->throws(ModelNotFoundException::class);
});

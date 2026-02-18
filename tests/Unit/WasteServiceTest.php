<?php

use App\Enums\PaymentStatus;
use App\Enums\WasteStatus;
use App\Enums\WasteType;
use App\Models\Household;
use App\Models\Payment;
use App\Models\User;
use App\Models\Waste;
use App\Models\WasteElectronic;
use App\Models\WasteOrganic;
use App\Services\WasteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    User::query()->delete();
    Household::query()->delete();
    Waste::query()->delete();
    Payment::query()->delete();
});

it('creates waste with the correct type', function () {
    $household = Household::factory()->create();
    $service = app(WasteService::class);

    $waste = $service->createPickup([
        'household_id' => $household->_id,
        'type' => 'organic',
    ]);

    expect($waste)->toBeInstanceOf(WasteOrganic::class)
        ->and($waste->type)->toBe(WasteType::Organic)
        ->and($waste->household_id)->toBe($household->_id);
});

it('sets default status to pending', function () {
    $household = Household::factory()->create();
    $service = app(WasteService::class);

    $waste = $service->createPickup([
        'household_id' => $household->_id,
        'type' => 'organic',
    ]);

    expect($waste->status)->toBe(WasteStatus::Pending);
});

describe('getPickups', function () {
    it('returns paginated results', function () {
        WasteOrganic::factory()->count(3)->create();

        $service = app(WasteService::class);
        $result = $service->getPickups([], 2);

        expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class)
            ->and($result->count())->toBe(2)
            ->and($result->total())->toBe(3);
    });

    it('returns all results as Collection when disablePagination is true', function () {
        WasteOrganic::factory()->count(5)->create();

        $service = app(WasteService::class);
        $result = $service->getPickups([], 10, true);

        expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
            ->and($result)->toHaveCount(5);
    });

    it('passes filters through to repository', function () {
        WasteOrganic::factory()->create(['status' => WasteStatus::Pending->value]);
        WasteOrganic::factory()->create(['status' => WasteStatus::Completed->value]);

        $service = app(WasteService::class);
        $result = $service->getPickups(['status' => 'pending']);

        expect($result->total())->toBe(1);
    });
});

it('throws when household has unpaid payments', function () {
    $household = Household::factory()->create();
    Payment::factory()->for($household)->create([
        'status' => PaymentStatus::Pending->value,
    ]);

    $service = app(WasteService::class);

    $service->createPickup([
        'household_id' => $household->_id,
        'type' => 'organic',
    ]);
})->throws(ValidationException::class);

describe('schedulePickup', function () {
    it('schedules a pending waste pickup', function () {
        $waste = WasteOrganic::factory()->create();
        $service = app(WasteService::class);
        $pickupDate = now()->format('Y-m-d');

        $result = $service->schedulePickup($waste->_id, $pickupDate);

        expect($result->status)->toBe(WasteStatus::Scheduled)
            ->and($result->pickup_date->format('Y-m-d'))->toBe($pickupDate);
    });

    it('throws ValidationException when status is not pending', function () {
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Completed->value,
        ]);
        $service = app(WasteService::class);

        $service->schedulePickup($waste->_id, now()->format('Y-m-d'));
    })->throws(ValidationException::class);

    it('throws ValidationException when pickup_date is before created_at', function () {
        $waste = WasteOrganic::factory()->create([
            'created_at' => now(),
        ]);
        $service = app(WasteService::class);

        $service->schedulePickup($waste->_id, now()->subDay()->format('Y-m-d'));
    })->throws(ValidationException::class);

    it('throws ValidationException when organic pickup_date exceeds 3 days', function () {
        $waste = WasteOrganic::factory()->create([
            'created_at' => now(),
        ]);
        $service = app(WasteService::class);

        $service->schedulePickup($waste->_id, now()->addDays(4)->format('Y-m-d'));
    })->throws(ValidationException::class);

    it('throws ModelNotFoundException when waste not found', function () {
        $service = app(WasteService::class);

        $service->schedulePickup('nonexistent-id', now()->format('Y-m-d'));
    })->throws(ModelNotFoundException::class);
});

describe('completePickup', function () {
    it('completes a scheduled waste pickup', function () {
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Scheduled->value,
        ]);
        $service = app(WasteService::class);

        $result = $service->completePickup($waste->_id);

        expect($result->status)->toBe(WasteStatus::Completed);
    });

    it('creates a payment record on completion', function () {
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Scheduled->value,
        ]);
        $service = app(WasteService::class);

        $service->completePickup($waste->_id);

        $payment = Payment::where('household_id', $waste->household_id)->first();

        expect($payment)->not->toBeNull()
            ->and($payment->status)->toBe(PaymentStatus::Pending)
            ->and($payment->household_id)->toBe($waste->household_id)
            ->and($payment->payment_date)->toBeNull();
    });

    it('payment amount is 50000 for organic', function () {
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Scheduled->value,
        ]);
        $service = app(WasteService::class);

        $service->completePickup($waste->_id);

        $payment = Payment::where('household_id', $waste->household_id)->first();

        expect((int) $payment->amount)->toBe(50000);
    });

    it('payment amount is 100000 for electronic', function () {
        $waste = WasteElectronic::factory()->create([
            'status' => WasteStatus::Scheduled->value,
        ]);
        $service = app(WasteService::class);

        $service->completePickup($waste->_id);

        $payment = Payment::where('household_id', $waste->household_id)->first();

        expect((int) $payment->amount)->toBe(100000);
    });

    it('throws ValidationException when status is not scheduled', function () {
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Pending->value,
        ]);
        $service = app(WasteService::class);

        $service->completePickup($waste->_id);
    })->throws(ValidationException::class);

    it('throws ModelNotFoundException when waste not found', function () {
        $service = app(WasteService::class);

        $service->completePickup('nonexistent-id');
    })->throws(ModelNotFoundException::class);
});

describe('cancelPickup', function () {
    it('cancels a scheduled waste pickup', function () {
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Scheduled->value,
        ]);
        $service = app(WasteService::class);

        $result = $service->cancelPickup($waste->_id);

        expect($result->status)->toBe(WasteStatus::Canceled);
    });

    it('throws ValidationException when status is not scheduled', function () {
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Pending->value,
        ]);
        $service = app(WasteService::class);

        $service->cancelPickup($waste->_id);
    })->throws(ValidationException::class);

    it('throws ModelNotFoundException when waste not found', function () {
        $service = app(WasteService::class);

        $service->cancelPickup('nonexistent-id');
    })->throws(ModelNotFoundException::class);
});

<?php

use App\Enums\PaymentStatus;
use App\Enums\WasteStatus;
use App\Models\Household;
use App\Models\Payment;
use App\Models\User;
use App\Models\Waste;
use App\Models\WasteOrganic;
use App\Models\WastePaper;
use App\Models\WastePlastic;
use App\Repositories\ReportRepository;

beforeEach(function () {
    User::query()->delete();
    Household::query()->delete();
    Waste::query()->delete();
    Payment::query()->delete();
});

it('returns grouped counts by type and status', function () {
    WasteOrganic::factory()->count(2)->create(['status' => WasteStatus::Pending->value]);
    WasteOrganic::factory()->count(1)->create(['status' => WasteStatus::Scheduled->value]);
    WastePlastic::factory()->count(3)->create(['status' => WasteStatus::Completed->value]);

    $repository = new ReportRepository;
    $results = $repository->getWasteSummary();

    $collection = collect($results);

    expect($collection)->toHaveCount(3)
        ->and($collection->where('type', 'organic')->where('status', 'pending')->first()['count'])->toBe(2)
        ->and($collection->where('type', 'organic')->where('status', 'scheduled')->first()['count'])->toBe(1)
        ->and($collection->where('type', 'plastic')->where('status', 'completed')->first()['count'])->toBe(3);
});

it('returns empty array when no waste data', function () {
    $repository = new ReportRepository;
    $results = $repository->getWasteSummary();

    expect($results)->toBeArray()->toBeEmpty();
});

describe('getPaymentSummary', function () {
    it('returns counts and amounts grouped by status', function () {
        Payment::factory()->count(2)->create(['status' => PaymentStatus::Pending->value, 'amount' => '100.00']);
        Payment::factory()->count(3)->paid()->create(['amount' => '50.00']);
        Payment::factory()->count(1)->failed()->create(['amount' => '75.00']);

        $repository = new ReportRepository;
        $results = $repository->getPaymentSummary();

        expect($results)->toHaveKey('pending')
            ->and($results['pending']['count'])->toBe(2)
            ->and($results['pending']['total_amount'])->toBe('200.00')
            ->and($results)->toHaveKey('paid')
            ->and($results['paid']['count'])->toBe(3)
            ->and($results['paid']['total_amount'])->toBe('150.00')
            ->and($results)->toHaveKey('failed')
            ->and($results['failed']['count'])->toBe(1)
            ->and($results['failed']['total_amount'])->toBe('75.00');
    });

    it('returns empty array when no payments exist', function () {
        $repository = new ReportRepository;
        $results = $repository->getPaymentSummary();

        expect($results)->toBeArray()->toBeEmpty();
    });

    it('omits status keys with no payments', function () {
        Payment::factory()->count(2)->paid()->create(['amount' => '100.00']);

        $repository = new ReportRepository;
        $results = $repository->getPaymentSummary();

        expect($results)->toHaveKey('paid')
            ->and($results)->not->toHaveKey('pending')
            ->and($results)->not->toHaveKey('failed');
    });
});

describe('getHouseholdPickupHistory', function () {
    it('returns pickup counts for a specific household', function () {
        $householdA = Household::factory()->create();
        $householdB = Household::factory()->create();

        WasteOrganic::factory()->count(2)->create([
            'household_id' => (string) $householdA->_id,
            'status' => WasteStatus::Pending->value,
        ]);
        WastePlastic::factory()->count(1)->create([
            'household_id' => (string) $householdA->_id,
            'status' => WasteStatus::Completed->value,
        ]);
        WastePaper::factory()->count(3)->create([
            'household_id' => (string) $householdB->_id,
            'status' => WasteStatus::Scheduled->value,
        ]);

        $repository = new ReportRepository;
        $results = $repository->getHouseholdPickupHistory((string) $householdA->_id);

        $collection = collect($results);

        expect($collection)->toHaveCount(2)
            ->and($collection->where('type', 'organic')->where('status', 'pending')->first()['count'])->toBe(2)
            ->and($collection->where('type', 'plastic')->where('status', 'completed')->first()['count'])->toBe(1);
    });

    it('returns empty array when household has no pickups', function () {
        $household = Household::factory()->create();

        $repository = new ReportRepository;
        $results = $repository->getHouseholdPickupHistory((string) $household->_id);

        expect($results)->toBeArray()->toBeEmpty();
    });
});

describe('getHouseholdPaymentHistory', function () {
    it('returns payment counts and amounts for a specific household', function () {
        $householdA = Household::factory()->create();
        $householdB = Household::factory()->create();

        Payment::factory()->count(2)->paid()->create([
            'household_id' => (string) $householdA->_id,
            'amount' => '100.00',
        ]);
        Payment::factory()->count(1)->create([
            'household_id' => (string) $householdB->_id,
            'status' => PaymentStatus::Pending->value,
        ]);

        $repository = new ReportRepository;
        $results = $repository->getHouseholdPaymentHistory((string) $householdA->_id);

        expect($results)->toHaveKey('paid')
            ->and($results['paid']['count'])->toBe(2)
            ->and($results['paid']['total_amount'])->toBe('200.00');
    });

    it('returns empty array when household has no payments', function () {
        $household = Household::factory()->create();

        $repository = new ReportRepository;
        $results = $repository->getHouseholdPaymentHistory((string) $household->_id);

        expect($results)->toBeArray()->toBeEmpty();
    });
});

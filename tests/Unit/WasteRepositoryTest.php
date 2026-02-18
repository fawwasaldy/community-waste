<?php

use App\Enums\WasteStatus;
use App\Enums\WasteType;
use App\Models\Household;
use App\Models\User;
use App\Models\Waste;
use App\Models\WasteElectronic;
use App\Models\WasteOrganic;
use App\Models\WastePaper;
use App\Models\WastePlastic;
use App\Repositories\WasteRepository;

beforeEach(function () {
    User::query()->delete();
    Household::query()->delete();
    Waste::query()->delete();
});

it('resolves correct model class for each type', function (string $type, string $expectedClass) {
    $repository = new WasteRepository;

    expect($repository->resolveModelClass($type))->toBe($expectedClass);
})->with([
    'organic' => ['organic', WasteOrganic::class],
    'plastic' => ['plastic', WastePlastic::class],
    'paper' => ['paper', WastePaper::class],
    'electronic' => ['electronic', WasteElectronic::class],
]);

it('creates correct model instance per type', function (string $type, string $expectedClass) {
    $household = Household::factory()->create();
    $repository = new WasteRepository;

    $waste = $repository->create($type, [
        'household_id' => $household->_id,
        'status' => 'pending',
    ]);

    expect($waste)->toBeInstanceOf($expectedClass)
        ->and($waste->type)->toBe(WasteType::from($type));
})->with([
    'organic' => ['organic', WasteOrganic::class],
    'plastic' => ['plastic', WastePlastic::class],
    'paper' => ['paper', WastePaper::class],
    'electronic' => ['electronic', WasteElectronic::class],
]);

describe('query', function () {
    it('returns all wastes when no filters', function () {
        WasteOrganic::factory()->count(2)->create();
        WastePlastic::factory()->create();

        $repository = new WasteRepository;
        $results = $repository->query()->get();

        expect($results)->toHaveCount(3);
    });

    it('filters by status', function () {
        WasteOrganic::factory()->create(['status' => 'pending']);
        WasteOrganic::factory()->create(['status' => 'completed']);

        $repository = new WasteRepository;
        $results = $repository->query(['status' => 'pending'])->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->status->value)->toBe('pending');
    });

    it('filters by type', function () {
        WasteOrganic::factory()->create();
        WastePlastic::factory()->create();

        $repository = new WasteRepository;
        $results = $repository->query(['type' => 'organic'])->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->type->value)->toBe('organic');
    });

    it('filters by household_id', function () {
        $household = Household::factory()->create();
        WasteOrganic::factory()->create(['household_id' => $household->_id]);
        WasteOrganic::factory()->create();

        $repository = new WasteRepository;
        $results = $repository->query(['household_id' => $household->_id])->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->household_id)->toBe($household->_id);
    });
});

describe('find', function () {
    it('finds waste by id', function () {
        $waste = WasteOrganic::factory()->create();
        $repository = new WasteRepository;

        $found = $repository->find($waste->_id);

        expect($found)->not->toBeNull()
            ->and($found->_id)->toBe($waste->_id);
    });

    it('returns null for non-existent id', function () {
        $repository = new WasteRepository;

        $found = $repository->find('nonexistent-id');

        expect($found)->toBeNull();
    });
});

describe('update', function () {
    it('updates pickup_date and status to scheduled', function () {
        $waste = WasteOrganic::factory()->create();
        $repository = new WasteRepository;
        $pickupDate = now()->format('Y-m-d');

        $updated = $repository->update($waste, [
            'pickup_date' => $pickupDate,
            'status' => WasteStatus::Scheduled->value,
        ]);

        expect($updated->status)->toBe(WasteStatus::Scheduled)
            ->and($updated->pickup_date->format('Y-m-d'))->toBe($pickupDate);
    });

    it('updates status to completed', function () {
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Scheduled->value,
        ]);
        $repository = new WasteRepository;

        $result = $repository->update($waste, [
            'status' => WasteStatus::Completed->value,
        ]);

        expect($result->status)->toBe(WasteStatus::Completed)
            ->and($waste->fresh()->status)->toBe(WasteStatus::Completed);
    });

    it('updates status to canceled', function () {
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Scheduled->value,
        ]);
        $repository = new WasteRepository;

        $result = $repository->update($waste, [
            'status' => WasteStatus::Canceled->value,
        ]);

        expect($result->status)->toBe(WasteStatus::Canceled)
            ->and($waste->fresh()->status)->toBe(WasteStatus::Canceled);
    });
});

it('includes safety_check for electronic type', function () {
    $household = Household::factory()->create();
    $repository = new WasteRepository;

    $waste = $repository->create('electronic', [
        'household_id' => $household->_id,
        'status' => 'pending',
        'safety_check' => true,
    ]);

    expect($waste)->toBeInstanceOf(WasteElectronic::class)
        ->and($waste->safety_check)->toBeTrue();
});

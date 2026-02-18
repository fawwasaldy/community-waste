<?php

use App\Models\Household;
use App\Services\HouseholdService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    Household::query()->delete();
});

describe('getHouseholds', function () {
    it('returns paginated results', function () {
        Household::factory()->count(3)->create();

        $service = app(HouseholdService::class);
        $result = $service->getHouseholds([], 2);

        expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class)
            ->and($result->count())->toBe(2)
            ->and($result->total())->toBe(3);
    });

    it('returns collection when disablePagination is true', function () {
        Household::factory()->count(3)->create();

        $service = app(HouseholdService::class);
        $result = $service->getHouseholds([], 10, true);

        expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
            ->and($result->count())->toBe(3);
    });

    it('passes filters through to repository', function () {
        Household::factory()->create(['block' => 'A']);
        Household::factory()->create(['block' => 'B']);

        $service = app(HouseholdService::class);
        $result = $service->getHouseholds(['block' => 'A']);

        expect($result->total())->toBe(1);
    });
});

describe('createHousehold', function () {
    it('creates and returns a household', function () {
        $service = app(HouseholdService::class);

        $household = $service->createHousehold([
            'owner_name' => 'John Doe',
            'address' => '123 Main St',
            'block' => 'A',
            'no' => '1',
        ]);

        expect($household)->toBeInstanceOf(Household::class)
            ->and($household->owner_name)->toBe('John Doe')
            ->and($household->block)->toBe('A');
    });
});

describe('findHousehold', function () {
    it('returns a household', function () {
        $household = Household::factory()->create();

        $service = app(HouseholdService::class);
        $result = $service->findHousehold($household->_id);

        expect($result)->toBeInstanceOf(Household::class)
            ->and($result->_id)->toBe($household->_id);
    });

    it('throws ModelNotFoundException for invalid id', function () {
        $service = app(HouseholdService::class);

        $service->findHousehold('nonexistent-id');
    })->throws(ModelNotFoundException::class);
});

describe('updateHousehold', function () {
    it('updates and returns the household', function () {
        $household = Household::factory()->create(['owner_name' => 'Old Name']);

        $service = app(HouseholdService::class);
        $result = $service->updateHousehold($household, ['owner_name' => 'New Name']);

        expect($result)->toBeInstanceOf(Household::class)
            ->and($result->owner_name)->toBe('New Name');
    });
});

describe('deleteHousehold', function () {
    it('soft deletes the household', function () {
        $household = Household::factory()->create();

        $service = app(HouseholdService::class);
        $service->deleteHousehold($household);

        expect(Household::find($household->_id))->toBeNull()
            ->and(Household::withTrashed()->find($household->_id))->not->toBeNull()
            ->and(Household::withTrashed()->find($household->_id)->trashed())->toBeTrue();
    });
});

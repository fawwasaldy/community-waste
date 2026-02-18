<?php

use App\Models\Household;
use App\Repositories\HouseholdRepository;

beforeEach(function () {
    Household::query()->delete();
});

describe('query', function () {
    it('returns all households when no filters', function () {
        Household::factory()->count(3)->create();

        $repository = new HouseholdRepository;
        $results = $repository->query()->get();

        expect($results)->toHaveCount(3);
    });

    it('filters by search matching owner_name', function () {
        Household::factory()->create(['owner_name' => 'John Doe']);
        Household::factory()->create(['owner_name' => 'Jane Smith']);

        $repository = new HouseholdRepository;
        $results = $repository->query(['search' => 'John'])->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->owner_name)->toBe('John Doe');
    });

    it('filters by search matching address', function () {
        Household::factory()->create(['address' => 'Jl. Merdeka No. 10']);
        Household::factory()->create(['address' => 'Jl. Sudirman No. 5']);

        $repository = new HouseholdRepository;
        $results = $repository->query(['search' => 'Merdeka'])->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->address)->toBe('Jl. Merdeka No. 10');
    });

    it('filters by search matching block', function () {
        Household::factory()->create(['block' => 'ZX']);
        Household::factory()->create(['block' => 'YW']);

        $repository = new HouseholdRepository;
        $results = $repository->query(['search' => 'ZX'])->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->block)->toBe('ZX');
    });

    it('filters by search matching no', function () {
        Household::factory()->create(['no' => '42']);
        Household::factory()->create(['no' => '99']);

        $repository = new HouseholdRepository;
        $results = $repository->query(['search' => '42'])->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->no)->toBe('42');
    });

    it('filters by block', function () {
        Household::factory()->create(['block' => 'A']);
        Household::factory()->create(['block' => 'B']);

        $repository = new HouseholdRepository;
        $results = $repository->query(['block' => 'A'])->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->block)->toBe('A');
    });

    it('filters by no', function () {
        Household::factory()->create(['no' => '10']);
        Household::factory()->create(['no' => '20']);

        $repository = new HouseholdRepository;
        $results = $repository->query(['no' => '10'])->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->no)->toBe('10');
    });

    it('combines multiple filters', function () {
        Household::factory()->create(['block' => 'A', 'no' => '1']);
        Household::factory()->create(['block' => 'A', 'no' => '2']);
        Household::factory()->create(['block' => 'B', 'no' => '1']);

        $repository = new HouseholdRepository;
        $results = $repository->query(['block' => 'A', 'no' => '1'])->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->block)->toBe('A')
            ->and($results->first()->no)->toBe('1');
    });
});

describe('create', function () {
    it('creates a household with correct attributes', function () {
        $repository = new HouseholdRepository;

        $household = $repository->create([
            'owner_name' => 'John Doe',
            'address' => 'Jl. Merdeka No. 10',
            'block' => 'A',
            'no' => '5',
        ]);

        expect($household)->toBeInstanceOf(Household::class)
            ->and($household->owner_name)->toBe('John Doe')
            ->and($household->address)->toBe('Jl. Merdeka No. 10')
            ->and($household->block)->toBe('A')
            ->and($household->no)->toBe('5');
    });
});

describe('find', function () {
    it('finds a household by id', function () {
        $household = Household::factory()->create();
        $repository = new HouseholdRepository;

        $found = $repository->find($household->_id);

        expect($found)->not->toBeNull()
            ->and($found->_id)->toBe($household->_id);
    });

    it('returns null for non-existent id', function () {
        $repository = new HouseholdRepository;

        $found = $repository->find('nonexistent-id');

        expect($found)->toBeNull();
    });
});

describe('update', function () {
    it('updates attributes and returns the household', function () {
        $household = Household::factory()->create(['owner_name' => 'Old Name']);
        $repository = new HouseholdRepository;

        $updated = $repository->update($household, ['owner_name' => 'New Name']);

        expect($updated)->toBeInstanceOf(Household::class)
            ->and($updated->owner_name)->toBe('New Name');
    });

    it('persists changes to the database', function () {
        $household = Household::factory()->create(['owner_name' => 'Old Name']);
        $repository = new HouseholdRepository;

        $repository->update($household, ['owner_name' => 'New Name']);

        expect($household->fresh()->owner_name)->toBe('New Name');
    });
});

describe('delete', function () {
    it('soft deletes the household', function () {
        $household = Household::factory()->create();
        $repository = new HouseholdRepository;

        $repository->delete($household);

        expect(Household::find($household->_id))->toBeNull()
            ->and(Household::withTrashed()->find($household->_id))->not->toBeNull()
            ->and(Household::withTrashed()->find($household->_id)->trashed())->toBeTrue();
    });
});

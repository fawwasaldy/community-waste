<?php

use App\Enums\PaymentStatus;
use App\Enums\WasteStatus;
use App\Enums\WasteType;
use App\Models\Household;
use App\Models\Payment;
use App\Models\User;
use App\Models\Waste;
use App\Models\WasteOrganic;
use App\Services\WasteService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    User::query()->delete();
    Household::query()->delete();
    Waste::query()->delete();
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

<?php

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

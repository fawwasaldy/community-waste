<?php

use App\Enums\PaymentStatus;
use App\Enums\WasteStatus;
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
        ->and($waste->type)->toBe('organic')
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

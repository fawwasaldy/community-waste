<?php

use App\Enums\WasteStatus;
use App\Models\Household;
use App\Models\Payment;
use App\Models\Waste;
use App\Models\WasteOrganic;
use App\Models\WastePlastic;

beforeEach(function () {
    Household::query()->delete();
    Waste::query()->delete();
});

describe('store', function () {
    it('creates an organic pickup', function () {
        $household = Household::factory()->create();

        $response = $this->postJson('/api/pickups', [
            'household_id' => $household->_id,
            'type' => 'organic',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'organic')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.household_id', $household->_id);
    });

    it('creates a plastic pickup', function () {
        $household = Household::factory()->create();

        $response = $this->postJson('/api/pickups', [
            'household_id' => $household->_id,
            'type' => 'plastic',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'plastic')
            ->assertJsonPath('data.status', 'pending');
    });

    it('creates a paper pickup', function () {
        $household = Household::factory()->create();

        $response = $this->postJson('/api/pickups', [
            'household_id' => $household->_id,
            'type' => 'paper',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'paper')
            ->assertJsonPath('data.status', 'pending');
    });

    it('creates an electronic pickup with safety_check', function () {
        $household = Household::factory()->create();

        $response = $this->postJson('/api/pickups', [
            'household_id' => $household->_id,
            'type' => 'electronic',
            'safety_check' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'electronic')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.safety_check', true);
    });

    it('rejects an invalid type', function () {
        $household = Household::factory()->create();

        $response = $this->postJson('/api/pickups', [
            'household_id' => $household->_id,
            'type' => 'invalid',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    });

    it('rejects missing household_id', function () {
        $response = $this->postJson('/api/pickups', [
            'type' => 'organic',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['household_id']);
    });

    it('rejects when household has unpaid payments', function () {
        $household = Household::factory()->create();
        Payment::factory()->for($household)->create();

        $response = $this->postJson('/api/pickups', [
            'household_id' => $household->_id,
            'type' => 'organic',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['household_id']);
    });
});

describe('cancel:expired-organic-waste', function () {
    it('cancels pending organic waste older than 3 days', function () {
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Pending->value,
            'created_at' => now()->subDays(4),
        ]);

        $this->artisan('cancel:expired-organic-waste')
            ->assertSuccessful();

        expect($waste->fresh()->status)->toBe(WasteStatus::Canceled);
    });

    it('cancels scheduled organic waste older than 3 days', function () {
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Scheduled->value,
            'created_at' => now()->subDays(4),
        ]);

        $this->artisan('cancel:expired-organic-waste')
            ->assertSuccessful();

        expect($waste->fresh()->status)->toBe(WasteStatus::Canceled);
    });

    it('does not cancel completed organic waste', function () {
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Completed->value,
            'created_at' => now()->subDays(4),
        ]);

        $this->artisan('cancel:expired-organic-waste')
            ->assertSuccessful();

        expect($waste->fresh()->status)->toBe(WasteStatus::Completed);
    });

    it('does not cancel organic waste younger than 3 days', function () {
        $waste = WasteOrganic::factory()->create([
            'status' => WasteStatus::Pending->value,
            'created_at' => now()->subDay(),
        ]);

        $this->artisan('cancel:expired-organic-waste')
            ->assertSuccessful();

        expect($waste->fresh()->status)->toBe(WasteStatus::Pending);
    });

    it('does not cancel non-organic waste older than 3 days', function () {
        $waste = WastePlastic::factory()->create([
            'status' => WasteStatus::Pending->value,
            'created_at' => now()->subDays(4),
        ]);

        $this->artisan('cancel:expired-organic-waste')
            ->assertSuccessful();

        expect($waste->fresh()->status)->toBe(WasteStatus::Pending);
    });
});

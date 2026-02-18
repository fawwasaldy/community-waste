<?php

use App\Models\Household;
use App\Models\Payment;
use App\Models\User;
use App\Models\Waste;

beforeEach(function () {
    User::query()->delete();
    Household::query()->delete();
    Waste::query()->delete();
    Payment::query()->delete();
});

describe('hasUnpaidPayments', function () {
    it('returns true when household has pending payments', function () {
        $household = Household::factory()->create();
        Payment::factory()->create(['household_id' => $household->_id, 'status' => 'pending']);

        expect($household->hasUnpaidPayments())->toBeTrue();
    });

    it('returns true when household has failed payments', function () {
        $household = Household::factory()->create();
        Payment::factory()->failed()->create(['household_id' => $household->_id]);

        expect($household->hasUnpaidPayments())->toBeTrue();
    });

    it('returns false when all payments are paid', function () {
        $household = Household::factory()->create();
        Payment::factory()->paid()->create(['household_id' => $household->_id]);

        expect($household->hasUnpaidPayments())->toBeFalse();
    });

    it('returns false when household has no payments', function () {
        $household = Household::factory()->create();

        expect($household->hasUnpaidPayments())->toBeFalse();
    });
});

<?php

use App\Enums\PaymentStatus;
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

describe('index', function () {
    it('returns 401 when unauthenticated', function () {
        $this->getJson('/api/payments')->assertUnauthorized();
    });

    it('returns paginated payments', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        Payment::factory()->count(3)->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/payments');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'household_id', 'amount', 'payment_date', 'status', 'created_at', 'updated_at']],
                'links',
                'meta',
            ]);
    });

    it('filters by status', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        Payment::factory()->create(['status' => PaymentStatus::Pending->value]);
        Payment::factory()->paid()->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/payments?status=pending');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');
    });

    it('filters by household_id', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $household = Household::factory()->create();
        Payment::factory()->create(['household_id' => $household->_id]);
        Payment::factory()->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/payments?household_id='.$household->_id);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.household_id', $household->_id);
    });

    it('filters by payment_date range', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        Payment::factory()->create(['payment_date' => '2025-01-15']);
        Payment::factory()->create(['payment_date' => '2025-02-15']);
        Payment::factory()->create(['payment_date' => '2025-03-15']);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/payments?payment_date_from=2025-01-01&payment_date_to=2025-01-31');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('combines multiple filters', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $household = Household::factory()->create();
        Payment::factory()->create(['household_id' => $household->_id, 'status' => PaymentStatus::Pending->value]);
        Payment::factory()->paid()->create(['household_id' => $household->_id]);
        Payment::factory()->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/payments?household_id='.$household->_id.'&status=pending');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('respects per_page parameter', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        Payment::factory()->count(5)->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/payments?per_page=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);
    });

    it('returns all results without pagination when disable_pagination is true', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        Payment::factory()->count(15)->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/payments?disable_pagination=1');

        $response->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonMissingPath('meta')
            ->assertJsonMissingPath('links');
    });

    it('returns empty data when no matches', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        Payment::factory()->paid()->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/payments?status=failed');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('rejects invalid payment_date_from format', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/payments?payment_date_from=01-2025-15');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_date_from']);
    });

    it('rejects invalid payment_date_to format', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/payments?payment_date_to=15/01/2025');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_date_to']);
    });
});

describe('store', function () {
    it('returns 401 when unauthenticated', function () {
        $household = Household::factory()->create();

        $response = $this->postJson('/api/payments', [
            'household_id' => $household->_id,
            'amount' => 50000,
        ]);

        $response->assertUnauthorized();
    });

    it('creates a payment with correct attributes', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $household = Household::factory()->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/payments', [
                'household_id' => $household->_id,
                'amount' => 50000,
            ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Payment created successfully.')
            ->assertJsonPath('data.household_id', $household->_id)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_date', null);

        $payment = Payment::where('household_id', $household->_id)->first();

        expect($payment)->not->toBeNull()
            ->and($payment->status)->toBe(PaymentStatus::Pending)
            ->and($payment->payment_date)->toBeNull();
    });

    it('returns 201 with correct JSON structure', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $household = Household::factory()->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/payments', [
                'household_id' => $household->_id,
                'amount' => 75000,
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'household_id', 'amount', 'payment_date', 'status', 'created_at', 'updated_at'],
            ]);
    });

    it('rejects missing household_id', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/payments', [
                'amount' => 50000,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['household_id']);
    });

    it('rejects missing amount', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $household = Household::factory()->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/payments', [
                'household_id' => $household->_id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    });

    it('rejects invalid household_id', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/payments', [
                'household_id' => 'nonexistent-id',
                'amount' => 50000,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['household_id']);
    });
});

describe('confirm', function () {
    it('returns 401 when unauthenticated', function () {
        $payment = Payment::factory()->create();

        $response = $this->putJson("/api/payments/{$payment->_id}/confirm", [
            'payment_date' => '2025-01-15',
            'status' => 'paid',
        ]);

        $response->assertUnauthorized();
    });

    it('confirms a pending payment as paid', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $payment = Payment::factory()->create(['payment_date' => null]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/payments/{$payment->_id}/confirm", [
                'payment_date' => '2025-01-15',
                'status' => 'paid',
            ]);

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Payment confirmed successfully.')
            ->assertJsonPath('data.status', 'paid');

        expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
            ->and($payment->fresh()->payment_date->format('Y-m-d'))->toBe('2025-01-15');
    });

    it('confirms a pending payment as failed', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $payment = Payment::factory()->create(['payment_date' => null]);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/payments/{$payment->_id}/confirm", [
                'payment_date' => '2025-02-10',
                'status' => 'failed',
            ]);

        $response->assertSuccessful()
            ->assertJsonPath('message', 'Payment confirmed successfully.')
            ->assertJsonPath('data.status', 'failed');

        expect($payment->fresh()->payment_date->format('Y-m-d'))->toBe('2025-02-10');
    });

    it('rejects confirming a non-pending payment', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $payment = Payment::factory()->paid()->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/payments/{$payment->_id}/confirm", [
                'payment_date' => '2025-01-15',
                'status' => 'paid',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });

    it('returns 404 for non-existent payment id', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson('/api/payments/nonexistent-id/confirm', [
                'payment_date' => '2025-01-15',
                'status' => 'paid',
            ]);

        $response->assertNotFound();
    });

    it('rejects missing payment_date', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $payment = Payment::factory()->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/payments/{$payment->_id}/confirm", [
                'status' => 'paid',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_date']);
    });

    it('rejects missing status', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $payment = Payment::factory()->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/payments/{$payment->_id}/confirm", [
                'payment_date' => '2025-01-15',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });

    it('rejects invalid status value', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $payment = Payment::factory()->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/payments/{$payment->_id}/confirm", [
                'payment_date' => '2025-01-15',
                'status' => 'pending',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });

    it('rejects invalid payment_date format', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);
        $payment = Payment::factory()->create();

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->putJson("/api/payments/{$payment->_id}/confirm", [
                'payment_date' => '15-01-2025',
                'status' => 'paid',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_date']);
    });
});

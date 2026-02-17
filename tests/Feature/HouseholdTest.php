<?php

use App\Models\Household;
use App\Models\User;

beforeEach(function () {
    User::query()->delete();
    Household::query()->delete();
});

function authHeader(): array
{
    $user = User::factory()->create();
    $token = auth()->login($user);

    return ['Authorization' => "Bearer $token"];
}

describe('index', function () {
    it('returns paginated households', function () {
        Household::factory()->count(3)->create();

        $response = $this->withHeaders(authHeader())
            ->getJson('/api/households');

        $response->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'owner_name', 'address', 'block', 'no', 'created_at', 'updated_at']]]);
    });

    it('searches households by keyword', function () {
        Household::factory()->create(['owner_name' => 'John Doe']);
        Household::factory()->create(['owner_name' => 'Jane Smith']);

        $response = $this->withHeaders(authHeader())
            ->getJson('/api/households?search=John');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.owner_name', 'John Doe');
    });

    it('filters households by block', function () {
        Household::factory()->create(['block' => 'A']);
        Household::factory()->create(['block' => 'B']);

        $response = $this->withHeaders(authHeader())
            ->getJson('/api/households?block=A');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.block', 'A');
    });

    it('filters households by block and no', function () {
        Household::factory()->create(['block' => 'A', 'no' => '1']);
        Household::factory()->create(['block' => 'A', 'no' => '2']);

        $response = $this->withHeaders(authHeader())
            ->getJson('/api/households?block=A&no=1');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.no', '1');
    });

    it('returns empty results when no match', function () {
        Household::factory()->create(['owner_name' => 'John Doe']);

        $response = $this->withHeaders(authHeader())
            ->getJson('/api/households?search=NonExistent');

        $response->assertSuccessful()
            ->assertJsonCount(0, 'data');
    });

    it('fails without authentication', function () {
        $this->getJson('/api/households')->assertUnauthorized();
    });
});

describe('store', function () {
    it('creates a new household', function () {
        $data = [
            'owner_name' => 'John Doe',
            'address' => '123 Main St',
            'block' => 'A',
            'no' => '1',
        ];

        $response = $this->withHeaders(authHeader())
            ->postJson('/api/households', $data);

        $response->assertCreated()
            ->assertJsonPath('data.owner_name', 'John Doe')
            ->assertJsonPath('data.block', 'A');

        $this->assertDatabaseHas('households', ['owner_name' => 'John Doe']);
    });

    it('fails with validation errors', function () {
        $response = $this->withHeaders(authHeader())
            ->postJson('/api/households', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['owner_name', 'address', 'block', 'no']);
    });

    it('fails without authentication', function () {
        $this->postJson('/api/households', [])->assertUnauthorized();
    });
});

describe('show', function () {
    it('returns a household by id', function () {
        $household = Household::factory()->create();

        $response = $this->withHeaders(authHeader())
            ->getJson("/api/households/{$household->_id}");

        $response->assertSuccessful()
            ->assertJsonPath('data.id', $household->_id);
    });

    it('returns 404 for invalid id', function () {
        $response = $this->withHeaders(authHeader())
            ->getJson('/api/households/nonexistent-id');

        $response->assertNotFound();
    });

    it('fails without authentication', function () {
        $household = Household::factory()->create();

        $this->getJson("/api/households/{$household->_id}")->assertUnauthorized();
    });
});

describe('update', function () {
    it('updates a household partially', function () {
        $household = Household::factory()->create(['owner_name' => 'Old Name']);

        $response = $this->withHeaders(authHeader())
            ->putJson("/api/households/{$household->_id}", ['owner_name' => 'New Name']);

        $response->assertSuccessful()
            ->assertJsonPath('data.owner_name', 'New Name');
    });

    it('returns 404 for invalid id', function () {
        $response = $this->withHeaders(authHeader())
            ->putJson('/api/households/nonexistent-id', ['owner_name' => 'Test']);

        $response->assertNotFound();
    });

    it('fails without authentication', function () {
        $household = Household::factory()->create();

        $this->putJson("/api/households/{$household->_id}", [])->assertUnauthorized();
    });
});

describe('destroy', function () {
    it('deletes a household', function () {
        $household = Household::factory()->create();

        $response = $this->withHeaders(authHeader())
            ->deleteJson("/api/households/{$household->_id}");

        $response->assertSuccessful()
            ->assertJson(['message' => 'Household deleted successfully.']);

        $this->assertDatabaseMissing('households', ['_id' => $household->_id]);
    });

    it('returns 404 for invalid id', function () {
        $response = $this->withHeaders(authHeader())
            ->deleteJson('/api/households/nonexistent-id');

        $response->assertNotFound();
    });

    it('fails without authentication', function () {
        $household = Household::factory()->create();

        $this->deleteJson("/api/households/{$household->_id}")->assertUnauthorized();
    });
});

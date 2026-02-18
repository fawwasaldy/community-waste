<?php

use App\Models\User;

beforeEach(function () {
    User::query()->delete();
});

describe('register', function () {
    it('registers a new user and returns a token', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    });

    it('fails with missing fields', function () {
        $response = $this->postJson('/api/register', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    });

    it('fails with duplicate email', function () {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('fails with short password', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });
});

describe('login', function () {
    it('logs in with valid credentials and returns a token', function () {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertSuccessful()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);
    });

    it('fails with invalid credentials', function () {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Invalid credentials.']);
    });

    it('fails with missing fields', function () {
        $response = $this->postJson('/api/login', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    });
});

describe('logout', function () {
    it('logs out an authenticated user', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/logout');

        $response->assertSuccessful()
            ->assertJson(['message' => 'Successfully logged out.']);
    });

    it('fails without authentication', function () {
        $response = $this->postJson('/api/logout');

        $response->assertUnauthorized();
    });
});

describe('me', function () {
    it('returns the authenticated user', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/me');

        $response->assertSuccessful()
            ->assertJsonFragment(['email' => $user->email]);
    });

    it('fails without authentication', function () {
        $response = $this->getJson('/api/me');

        $response->assertUnauthorized();
    });
});

describe('refresh', function () {
    it('returns a new token', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/refresh');

        $response->assertSuccessful()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);
    });

    it('fails without authentication', function () {
        $response = $this->postJson('/api/refresh');

        $response->assertUnauthorized();
    });
});

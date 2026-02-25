<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('POST /api/v1/auth/register', function () {
    it('registers a new user and returns a token', function () {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    });

    it('fails when email is already taken', function () {
        User::factory()->create(['email' => 'john@example.com']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertUnprocessable();
    });

    it('validates required fields', function (array $payload) {
        $this->postJson('/api/v1/auth/register', $payload)
            ->assertUnprocessable();
    })->with([
        'missing name' => [['email' => 'john@example.com', 'password' => 'password', 'password_confirmation' => 'password']],
        'missing email' => [['name' => 'John', 'password' => 'password', 'password_confirmation' => 'password']],
        'missing password' => [['name' => 'John', 'email' => 'john@example.com']],
        'password mismatch' => [['name' => 'John', 'email' => 'john@example.com', 'password' => 'password', 'password_confirmation' => 'wrong']],
    ]);
});

describe('POST /api/v1/auth/login', function () {
    it('returns a token for valid credentials', function () {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertSuccessful()
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);
    });

    it('rejects invalid credentials', function () {
        User::factory()->create(['email' => 'john@example.com']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    });
});

describe('POST /api/v1/auth/logout', function () {
    it('revokes the current token', function () {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertSuccessful();

        expect($user->tokens()->count())->toBe(0);
    });

    it('requires authentication', function () {
        $this->postJson('/api/v1/auth/logout')
            ->assertUnauthorized();
    });
});

describe('GET /api/v1/auth/me', function () {
    it('returns the authenticated user', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/auth/me')
            ->assertSuccessful()
            ->assertJson(['data' => ['id' => $user->id, 'email' => $user->email]]);
    });

    it('requires authentication', function () {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    });
});

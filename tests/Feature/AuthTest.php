<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login screen can be rendered', function () {
    $response = $this->get('/admin/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create([
        'role' => 'super_admin',
        'is_active' => true,
    ]);

    $this->actingAs($user);
    $this->assertAuthenticatedAs($user);
});

test('inactive users cannot access panel', function () {
    $user = User::factory()->create([
        'role' => 'super_admin',
        'is_active' => false,
    ]);

    expect($user->canAccessPanel(filament()->getCurrentPanel() ?? filament()->getDefaultPanel()))->toBeFalse();
});

test('customer users cannot access panel', function () {
    $user = User::factory()->create([
        'role' => 'customer',
        'is_active' => true,
    ]);

    expect($user->canAccessPanel(filament()->getCurrentPanel() ?? filament()->getDefaultPanel()))->toBeFalse();
});

test('approved scope query returns only approved users', function () {
    User::factory()->approved()->create(['email' => 'approved1@test.com']);
    User::factory()->approved()->create(['email' => 'approved2@test.com']);
    User::factory()->pendingApproval()->create(['email' => 'pending@test.com']);
    User::factory()->rejected()->create(['email' => 'rejected@test.com']);

    $approvedUsers = User::approved()->get();

    expect($approvedUsers)->toHaveCount(2)
        ->and($approvedUsers->pluck('email')->toArray())
        ->toContain('approved1@test.com', 'approved2@test.com')
        ->not->toContain('pending@test.com', 'rejected@test.com');
});

test('pending approval scope query returns only pending users', function () {
    User::factory()->approved()->create();
    User::factory()->pendingApproval()->create(['email' => 'pending1@test.com']);
    User::factory()->pendingApproval()->create(['email' => 'pending2@test.com']);
    User::factory()->rejected()->create();

    $pendingUsers = User::pendingApproval()->get();

    expect($pendingUsers)->toHaveCount(2);
});

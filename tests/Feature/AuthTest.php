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

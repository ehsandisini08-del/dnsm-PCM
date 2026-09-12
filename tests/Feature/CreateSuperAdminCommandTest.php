<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('command creates super admin successfully in non-interactive mode', function () {
    $this->artisan('app:create-super-admin', [
        '--email' => 'testadmin@example.com',
        '--password' => 'SecurePassword123',
        '--name' => 'Test Admin',
    ])->assertExitCode(0);

    $user = User::where('email', 'testadmin@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Test Admin')
        ->and($user->role)->toBe('super_admin')
        ->and($user->is_active)->toBeTrue()
        ->and($user->approval_status)->toBe('approved')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->approved_at)->not->toBeNull();
});

test('command fails with duplicate email', function () {
    User::factory()->create(['email' => 'duplicate@example.com']);

    $this->artisan('app:create-super-admin', [
        '--email' => 'duplicate@example.com',
        '--password' => 'SecurePassword123',
    ])->assertExitCode(1);
});

test('command fails with invalid email format', function () {
    $this->artisan('app:create-super-admin', [
        '--email' => 'invalid-email',
        '--password' => 'SecurePassword123',
    ])->assertExitCode(1);
});

test('command fails with short password', function () {
    $this->artisan('app:create-super-admin', [
        '--email' => 'admin@example.com',
        '--password' => 'short',
    ])->assertExitCode(1);
});

test('created super admin can access panel', function () {
    $this->artisan('app:create-super-admin', [
        '--email' => 'admin@example.com',
        '--password' => 'SecurePassword123',
    ])->assertExitCode(0);

    $user = User::where('email', 'admin@example.com')->first();

    expect($user->canAccessPanel(filament()->getDefaultPanel()))->toBeTrue();
});

test('created super admin has all required fields set', function () {
    $this->artisan('app:create-super-admin', [
        '--email' => 'admin@example.com',
        '--password' => 'SecurePassword123',
        '--name' => 'Custom Admin Name',
    ])->assertExitCode(0);

    $user = User::where('email', 'admin@example.com')->first();

    expect($user->name)->toBe('Custom Admin Name')
        ->and($user->email)->toBe('admin@example.com')
        ->and($user->role)->toBe('super_admin')
        ->and($user->is_active)->toBeTrue()
        ->and($user->approval_status)->toBe('approved')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->approved_at)->not->toBeNull()
        ->and($user->approved_by)->toBeNull()
        ->and($user->isApproved())->toBeTrue()
        ->and($user->isSuperAdmin())->toBeTrue();
});

test('command uses default name if not provided', function () {
    $this->artisan('app:create-super-admin', [
        '--email' => 'admin@example.com',
        '--password' => 'SecurePassword123',
    ])->assertExitCode(0);

    $user = User::where('email', 'admin@example.com')->first();

    expect($user->name)->toBe('Super Admin');
});

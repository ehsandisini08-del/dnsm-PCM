<?php

use App\Filament\Pages\SslSettings;
use App\Models\User;
use App\Services\SslService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('super admin can access ssl settings page', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin);

    Livewire::test(SslSettings::class)
        ->assertSuccessful()
        ->assertSee('Status Sertifikat SSL / TLS');
});

test('non-super admin cannot access ssl settings page', function () {
    $operator = User::factory()->operator()->create();

    $this->actingAs($operator);

    expect(SslSettings::canAccess())->toBeFalse();
});

test('guest cannot access ssl settings page', function () {
    expect(SslSettings::canAccess())->toBeFalse();
});

test('ssl service returns structured status array', function () {
    $service = app(SslService::class);
    $status = $service->getSslStatus('test.example.com');

    expect($status)->toBeArray()
        ->toHaveKeys([
            'domain',
            'is_installed',
            'is_valid',
            'is_expired',
            'is_expiring_soon',
            'issuer',
            'valid_from',
            'valid_to',
            'days_remaining',
            'certbot_available',
            'nginx_available',
            'ssl_enabled_in_nginx',
            'app_url',
            'is_https',
        ])
        ->and($status['domain'])->toBe('test.example.com');
});

test('ssl service validates empty domain and email', function () {
    $service = app(SslService::class);

    $result1 = $service->issueCertificate('', 'admin@example.com');
    expect($result1['success'])->toBeFalse()
        ->and($result1['message'])->toContain('domain');

    $result2 = $service->issueCertificate('example.com', 'invalid-email');
    expect($result2['success'])->toBeFalse()
        ->and($result2['message'])->toContain('email');
});

test('ssl setup command status option executes successfully', function () {
    $this->artisan('app:ssl-setup', ['--status' => true])
        ->assertExitCode(0);
});

test('ssl settings page validates form inputs before issuing ssl', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $this->actingAs($superAdmin);

    Livewire::test(SslSettings::class)
        ->set('domain', '')
        ->set('email', 'invalid-email')
        ->call('requestSsl')
        ->assertHasErrors(['domain', 'email']);
});

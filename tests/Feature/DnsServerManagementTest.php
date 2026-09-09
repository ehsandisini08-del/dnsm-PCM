<?php

use App\Filament\Resources\DnsServers\Pages\CreateDnsServer;
use App\Filament\Resources\DnsServers\Pages\EditDnsServer;
use App\Filament\Resources\DnsServers\Pages\ListDnsServers;
use App\Filament\Resources\DnsServers\Pages\ViewDnsServer;
use App\Models\DnsServer;
use App\Models\ServerHealthCheck;
use App\Models\User;
use App\Services\PowerDNSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->superAdmin()->create();
    $this->actingAs($this->admin);
});

test('dns server list page can be rendered', function () {
    $server = DnsServer::factory()->create(['name' => 'NS1 Jakarta']);

    Livewire::test(ListDnsServers::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$server]);
});

test('dns server create page creates a new server', function () {
    Livewire::test(CreateDnsServer::class)
        ->fillForm([
            'name' => 'NS2 Surabaya',
            'hostname' => 'ns2.pelangicomm.net',
            'ip_address' => '103.50.50.2',
            'type' => 'authoritative',
            'port' => 53,
            'status' => 'offline',
            'api_url' => 'http://103.50.50.2:8081',
            'api_key' => 'secret-pdns-key',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $server = DnsServer::where('hostname', 'ns2.pelangicomm.net')->first();
    expect($server)->not->toBeNull()
        ->and($server->name)->toBe('NS2 Surabaya')
        ->and($server->ip_address)->toBe('103.50.50.2');
});

test('dns server edit page updates server attributes', function () {
    $server = DnsServer::factory()->create();

    Livewire::test(EditDnsServer::class, ['record' => $server->id])
        ->fillForm([
            'name' => 'NS1 Primary Updated',
            'port' => 5353,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $server->refresh();
    expect($server->name)->toBe('NS1 Primary Updated')
        ->and($server->port)->toBe(5353);
});

test('dns server view page renders properly', function () {
    $server = DnsServer::factory()->create();

    Livewire::test(ViewDnsServer::class, ['record' => $server->id])
        ->assertSuccessful();
});

test('checkServerHealth updates server status and records history', function () {
    $server = DnsServer::factory()->create([
        'ip_address' => '127.0.0.1',
        'port' => 9999, // Unused port, should result in offline
        'api_url' => null,
        'api_key' => null,
    ]);

    $service = app(PowerDNSService::class);
    $result = $service->checkServerHealth($server);

    expect($result)->toBeArray()
        ->and($result['status'])->toBe('offline');

    $server->refresh();
    expect($server->status)->toBe('offline')
        ->and($server->last_check_at)->not->toBeNull();

    // Verify history in server_health_checks table
    $history = ServerHealthCheck::where('dns_server_id', $server->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->status)->toBe('offline');
});

test('artisan command dns:check-servers executes successfully with sync option', function () {
    DnsServer::factory()->count(2)->create([
        'ip_address' => '127.0.0.1',
        'port' => 9999,
        'api_url' => null,
    ]);

    $this->artisan('dns:check-servers', ['--sync' => true])
        ->assertSuccessful();

    expect(ServerHealthCheck::count())->toBe(2);
});

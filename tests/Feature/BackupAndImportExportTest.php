<?php

use App\Filament\Pages\Backups;
use App\Models\PdnsDomain;
use App\Models\PdnsRecord;
use App\Models\User;
use App\Services\BackupService;
use App\Services\BindZoneParser;
use App\Services\PowerDNSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->superAdmin()->create();
    $this->actingAs($this->admin);
    $this->service = app(PowerDNSService::class);
    $this->backupService = app(BackupService::class);
});

// --- BIND Parser & Import ---

test('bind zone parser parses origin, ttl and records correctly', function () {
    $bindContent = <<<'BIND'
$ORIGIN example.com.
$TTL 3600
@   IN  SOA  ns1.example.com. hostmaster.example.com. 2026090801 10800 3600 604800 3600
@   IN  NS   ns1.example.com.
@   IN  NS   ns2.example.com.
@   IN  A    103.10.10.1
www IN  CNAME example.com.
mail IN A    103.10.10.2
@   IN  MX   10 mail.example.com.
@   IN  TXT  "v=spf1 include:_spf.example.com ~all"
BIND;

    $parsed = BindZoneParser::parse($bindContent);

    expect($parsed['origin'])->toBe('example.com')
        ->and($parsed['ttl'])->toBe(3600)
        ->and(count($parsed['records']))->toBe(8);
});

test('bind zone parser imports zone and records into database', function () {
    $bindContent = <<<'BIND'
$ORIGIN importeddomain.net.
$TTL 1800
@   IN  SOA  ns1.importeddomain.net. hostmaster.importeddomain.net. 2026090801 10800 3600 604800 3600
@   IN  NS   ns1.importeddomain.net.
@   IN  A    103.55.55.1
api IN  A    103.55.55.2
BIND;

    $parsed = BindZoneParser::parse($bindContent);
    $domain = BindZoneParser::import($parsed);

    expect($domain)->toBeInstanceOf(PdnsDomain::class)
        ->and($domain->name)->toBe('importeddomain.net')
        ->and($domain->records()->count())->toBe(4);

    $apiRecord = PdnsRecord::where('domain_id', $domain->id)->where('name', 'api.importeddomain.net')->first();
    expect($apiRecord)->not->toBeNull()
        ->and($apiRecord->content)->toBe('103.55.55.2')
        ->and($apiRecord->ttl)->toBe(1800);
});

test('json importer imports domain and records structure', function () {
    $jsonData = [
        'name' => 'jsonimport.org',
        'type' => 'NATIVE',
        'records' => [
            ['name' => '@', 'type' => 'A', 'content' => '103.77.77.1', 'ttl' => 3600],
            ['name' => 'cdn', 'type' => 'CNAME', 'content' => 'cdn.cloudflare.com.', 'ttl' => 3600],
        ],
    ];

    $domain = BindZoneParser::importJson($jsonData);

    expect($domain)->toBeInstanceOf(PdnsDomain::class)
        ->and($domain->name)->toBe('jsonimport.org')
        ->and($domain->records()->count())->toBe(2);
});

// --- Backup Service & Filament Page ---

test('backup service creates, lists, restores and deletes snapshots', function () {
    $domain = $this->service->createZone(['name' => 'backuptestdomain.com']);
    $this->service->createRecord($domain, ['name' => 'web', 'type' => 'A', 'content' => '103.11.11.1']);

    $result = $this->backupService->createBackup('unit_test_backup');

    expect($result)->toHaveKeys(['filename', 'path', 'total_zones', 'total_records'])
        ->and($result['total_zones'])->toBeGreaterThanOrEqual(1);

    // List backups
    $list = $this->backupService->listBackups();
    expect(count($list))->toBeGreaterThanOrEqual(1)
        ->and($list[0]['filename'])->toBe($result['filename']);

    // Delete domain to test restore
    $domain->delete();
    expect(PdnsDomain::where('name', 'backuptestdomain.com')->exists())->toBeFalse();

    // Restore
    $restored = $this->backupService->restoreBackup($result['filename']);
    expect($restored)->toBeTrue()
        ->and(PdnsDomain::where('name', 'backuptestdomain.com')->exists())->toBeTrue();

    // Delete backup file
    $deleted = $this->backupService->deleteBackup($result['filename']);
    expect($deleted)->toBeTrue();
});

test('backups filament page renders and can trigger backup creation', function () {
    Livewire::test(Backups::class)
        ->assertSuccessful()
        ->set('backupName', 'ui_test_snapshot')
        ->call('createNewBackup')
        ->assertHasNoErrors();

    $backups = $this->backupService->listBackups();
    expect(count($backups))->toBeGreaterThanOrEqual(1);

    // Clean up created test backup
    foreach ($backups as $b) {
        if (str_contains($b['filename'], 'ui_test_snapshot')) {
            $this->backupService->deleteBackup($b['filename']);
        }
    }
});

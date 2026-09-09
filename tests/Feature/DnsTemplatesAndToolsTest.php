<?php

use App\Filament\Pages\DnsMonitoring;
use App\Filament\Pages\DnsTools;
use App\Filament\Resources\DnsTemplates\Pages\CreateDnsTemplate;
use App\Filament\Resources\DnsTemplates\Pages\EditDnsTemplate;
use App\Filament\Resources\DnsTemplates\Pages\ListDnsTemplates;
use App\Filament\Resources\DnsTemplates\Pages\ViewDnsTemplate;
use App\Models\DnsServer;
use App\Models\DnsTemplate;
use App\Models\DnsTemplateRecord;
use App\Models\PdnsRecord;
use App\Models\User;
use App\Services\PowerDNSService;
use Database\Seeders\DnsTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->superAdmin()->create();
    $this->actingAs($this->admin);
    $this->service = app(PowerDNSService::class);
});

// --- DNS Templates ---

test('dns template seeder creates standard templates with records', function () {
    $this->seed(DnsTemplateSeeder::class);

    expect(DnsTemplate::count())->toBeGreaterThanOrEqual(3);

    $webTpl = DnsTemplate::where('name', 'Standard Web Hosting')->first();
    expect($webTpl)->not->toBeNull()
        ->and($webTpl->records()->count())->toBe(3);

    $gsuite = DnsTemplate::where('name', 'Google Workspace (GSuite)')->first();
    expect($gsuite)->not->toBeNull()
        ->and($gsuite->records()->where('type', 'MX')->count())->toBe(5);
});

test('apply template generates records with variable replacement', function () {
    $template = DnsTemplate::create([
        'name' => 'Custom App Template',
        'description' => 'Custom template for testing',
        'is_active' => true,
    ]);

    DnsTemplateRecord::create([
        'dns_template_id' => $template->id,
        'name' => '@',
        'type' => 'A',
        'value' => '{ip}',
        'ttl' => 1800,
    ]);

    DnsTemplateRecord::create([
        'dns_template_id' => $template->id,
        'name' => 'api',
        'type' => 'A',
        'value' => '{ip}',
        'ttl' => 1800,
    ]);

    DnsTemplateRecord::create([
        'dns_template_id' => $template->id,
        'name' => '@',
        'type' => 'TXT',
        'value' => 'v=spf1 include:_spf.{domain} ~all',
        'ttl' => 3600,
    ]);

    $domain = $this->service->createZone(['name' => 'templatingtest.com']);

    $created = $this->service->applyTemplate($domain, $template, [
        'ip' => '103.88.99.1',
    ]);

    expect(count($created))->toBe(3);

    $apexRecord = PdnsRecord::where('domain_id', $domain->id)->where('name', 'templatingtest.com')->where('type', 'A')->first();
    expect($apexRecord)->not->toBeNull()
        ->and($apexRecord->content)->toBe('103.88.99.1');

    $apiRecord = PdnsRecord::where('domain_id', $domain->id)->where('name', 'api.templatingtest.com')->first();
    expect($apiRecord)->not->toBeNull()
        ->and($apiRecord->content)->toBe('103.88.99.1');

    $txtRecord = PdnsRecord::where('domain_id', $domain->id)->where('type', 'TXT')->first();
    expect($txtRecord)->not->toBeNull()
        ->and($txtRecord->content)->toContain('templatingtest.com');
});

test('dns template list page can be rendered', function () {
    $template = DnsTemplate::create(['name' => 'Test Tpl', 'is_active' => true]);

    Livewire::test(ListDnsTemplates::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$template]);
});

test('dns template create page creates a new template', function () {
    Livewire::test(CreateDnsTemplate::class)
        ->fillForm([
            'name' => 'Microservice Cluster',
            'description' => 'For containerized clusters',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $tpl = DnsTemplate::where('name', 'Microservice Cluster')->first();
    expect($tpl)->not->toBeNull()
        ->and($tpl->description)->toBe('For containerized clusters');
});

test('dns template edit page updates template', function () {
    $template = DnsTemplate::create(['name' => 'Old Template Name', 'is_active' => true]);

    Livewire::test(EditDnsTemplate::class, ['record' => $template->id])
        ->fillForm([
            'name' => 'Updated Template Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $template->refresh();
    expect($template->name)->toBe('Updated Template Name');
});

test('dns template view page renders properly', function () {
    $template = DnsTemplate::create(['name' => 'View Tpl', 'is_active' => true]);

    Livewire::test(ViewDnsTemplate::class, ['record' => $template->id])
        ->assertSuccessful();
});

// --- DNS Diagnostics & Tools ---

test('lookupDns resolves local domain records correctly', function () {
    $domain = $this->service->createZone(['name' => 'diagnosticdomain.com']);
    $this->service->createRecord($domain, [
        'name' => 'web',
        'type' => 'A',
        'content' => '103.1.2.3',
    ]);

    $results = $this->service->lookupDns('diagnosticdomain.com', 'A');

    expect($results)->toBeArray()
        ->and($results)->toHaveKeys(['server', 'latency_ms', 'count', 'records'])
        ->and($results['count'])->toBeGreaterThanOrEqual(1);
});

test('checkPropagation returns status from multiple resolvers', function () {
    $results = $this->service->checkPropagation('example.com', 'A');

    expect($results)->toBeArray()
        ->and(count($results))->toBe(5)
        ->and($results[0])->toHaveKeys(['provider', 'ip', 'location', 'status', 'latency_ms', 'result']);
});

test('dns tools page renders and runs lookup & propagation', function () {
    Livewire::test(DnsTools::class)
        ->assertSuccessful()
        ->set('lookupDomain', 'example.com')
        ->set('lookupType', 'A')
        ->call('runLookup')
        ->assertHasNoErrors();
});

// --- Monitoring Page ---

test('dns monitoring page renders and can execute diagnostics', function () {
    DnsServer::factory()->create(['name' => 'NS1 Primary Node']);

    Livewire::test(DnsMonitoring::class)
        ->assertSuccessful()
        ->call('runAllHealthChecks')
        ->assertHasNoErrors();
});

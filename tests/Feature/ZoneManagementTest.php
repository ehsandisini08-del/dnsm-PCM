<?php

use App\Filament\Resources\PdnsDomains\Pages\CreatePdnsDomain;
use App\Filament\Resources\PdnsDomains\Pages\EditPdnsDomain;
use App\Filament\Resources\PdnsDomains\Pages\ListPdnsDomains;
use App\Filament\Resources\PdnsDomains\Pages\ViewPdnsDomain;
use App\Models\Customer;
use App\Models\PdnsDomain;
use App\Models\User;
use App\Services\PowerDNSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'super_admin',
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);
});

test('zone list page can be rendered', function () {
    $domain = app(PowerDNSService::class)->createZone(['name' => 'pelangicomm.net']);

    Livewire::test(ListPdnsDomains::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$domain]);
});

test('zone create page can create a new zone with soa and ns', function () {
    Livewire::test(CreatePdnsDomain::class)
        ->fillForm([
            'name' => 'ispnet.id',
            'type' => 'NATIVE',
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $domain = PdnsDomain::where('name', 'ispnet.id')->first();
    expect($domain)->not->toBeNull()
        ->and($domain->records()->where('type', 'SOA')->count())->toBe(1)
        ->and($domain->records()->where('type', 'NS')->count())->toBeGreaterThanOrEqual(1);
});

test('zone edit page can update zone details', function () {
    $domain = app(PowerDNSService::class)->createZone(['name' => 'clientdomain.com']);
    $customer = Customer::create([
        'name' => 'ISP Customer A',
        'email' => 'client@isp.com',
    ]);

    Livewire::test(EditPdnsDomain::class, ['record' => $domain->id])
        ->fillForm([
            'customer_id' => $customer->id,
            'status' => 'disabled',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $domain->refresh();
    expect($domain->customer_id)->toBe($customer->id)
        ->and($domain->status)->toBe('disabled');
});

test('zone view page renders properly', function () {
    $domain = app(PowerDNSService::class)->createZone(['name' => 'viewzone.org']);

    Livewire::test(ViewPdnsDomain::class, ['record' => $domain->id])
        ->assertSuccessful();
});

test('zone export bind format produces valid zone file', function () {
    $domain = app(PowerDNSService::class)->createZone(['name' => 'exporttest.com']);
    app(PowerDNSService::class)->createRecord($domain, [
        'name' => 'www',
        'type' => 'A',
        'content' => '103.10.10.100',
    ]);

    $bindContent = app(PowerDNSService::class)->exportBindFormat($domain);

    expect($bindContent)->toContain('$ORIGIN exporttest.com.')
        ->and($bindContent)->toContain('SOA')
        ->and($bindContent)->toContain('www')
        ->and($bindContent)->toContain('103.10.10.100');
});

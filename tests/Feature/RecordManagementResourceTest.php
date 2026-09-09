<?php

use App\Filament\Resources\PdnsRecords\Pages\CreatePdnsRecord;
use App\Filament\Resources\PdnsRecords\Pages\EditPdnsRecord;
use App\Filament\Resources\PdnsRecords\Pages\ListPdnsRecords;
use App\Models\PdnsRecord;
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
    $this->service = app(PowerDNSService::class);
    $this->domain = $this->service->createZone(['name' => 'recordsite.com']);
});

test('record list page can be rendered and see records', function () {
    $record = $this->service->createRecord($this->domain, [
        'name' => 'web',
        'type' => 'A',
        'content' => '103.10.10.50',
    ]);

    Livewire::test(ListPdnsRecords::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$record]);
});

test('record create page can create a new A record and updates soa', function () {
    $initialSoa = $this->domain->soaRecord()->first()->content;

    Livewire::test(CreatePdnsRecord::class)
        ->fillForm([
            'domain_id' => $this->domain->id,
            'type' => 'A',
            'name' => 'mail',
            'content' => '103.10.10.55',
            'ttl' => 3600,
            'disabled' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $record = PdnsRecord::where('name', 'mail.recordsite.com')->first();
    expect($record)->not->toBeNull()
        ->and($record->content)->toBe('103.10.10.55')
        ->and($record->type)->toBe('A');

    $this->domain->refresh();
    expect($this->domain->soaRecord()->first()->content)->not->toBe($initialSoa);
});

test('record edit page can update record content and updates soa', function () {
    $record = $this->service->createRecord($this->domain, [
        'name' => 'api',
        'type' => 'A',
        'content' => '103.10.10.60',
    ]);

    $initialSoa = $this->domain->fresh()->soaRecord()->first()->content;

    Livewire::test(EditPdnsRecord::class, ['record' => $record->id])
        ->fillForm([
            'content' => '103.10.10.65',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $record->refresh();
    expect($record->content)->toBe('103.10.10.65');

    $this->domain->refresh();
    expect($this->domain->soaRecord()->first()->content)->not->toBe($initialSoa);
});

test('record can be deleted via service and soa is updated', function () {
    $record = $this->service->createRecord($this->domain, [
        'name' => 'temp',
        'type' => 'A',
        'content' => '103.10.10.70',
    ]);

    $initialSoa = $this->domain->fresh()->soaRecord()->first()->content;

    $deleted = $this->service->deleteRecord($record);
    expect($deleted)->toBeTrue()
        ->and(PdnsRecord::find($record->id))->toBeNull();

    $this->domain->refresh();
    expect($this->domain->soaRecord()->first()->content)->not->toBe($initialSoa);
});

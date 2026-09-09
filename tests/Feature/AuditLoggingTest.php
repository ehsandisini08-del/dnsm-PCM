<?php

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Models\AuditLog;
use App\Models\PdnsDomain;
use App\Models\User;
use App\Services\PowerDNSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->superAdmin()->create();
    $this->actingAs($this->admin);
    $this->service = app(PowerDNSService::class);
});

test('creating zone automatically triggers audit log', function () {
    $domain = $this->service->createZone(['name' => 'auditeddomain.com']);

    $log = AuditLog::where('action', 'CREATE_ZONE')
        ->where('model_type', PdnsDomain::class)
        ->where('model_id', $domain->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($this->admin->id)
        ->and($log->new_values['name'])->toBe('auditeddomain.com');
});

test('updating zone triggers audit log with changes diff', function () {
    $domain = $this->service->createZone(['name' => 'updatetest.com']);
    $this->service->updateZone($domain, ['status' => 'disabled']);

    $log = AuditLog::where('action', 'UPDATE_ZONE')
        ->where('model_id', $domain->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->new_values['status'])->toBe('disabled');
});

test('creating record triggers audit log', function () {
    $domain = $this->service->createZone(['name' => 'recordaudited.com']);
    $record = $this->service->createRecord($domain, [
        'name' => 'vpn',
        'type' => 'A',
        'content' => '103.10.10.150',
    ]);

    $log = AuditLog::where('action', 'CREATE_RECORD')
        ->where('model_id', $record->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->new_values['name'])->toBe('vpn.recordaudited.com')
        ->and($log->new_values['type'])->toBe('A');
});

test('user creation masks password in audit log', function () {
    $newUser = User::create([
        'name' => 'Audited Operator',
        'email' => 'op.audit@isp.com',
        'password' => 'secretplainpassword',
        'role' => 'operator',
        'is_active' => true,
    ]);

    $log = AuditLog::where('action', 'CREATE_USER')
        ->where('model_id', $newUser->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->new_values['password'])->toBe('********');
});

test('audit log list page can be rendered', function () {
    $domain = $this->service->createZone(['name' => 'listview.com']);

    Livewire::test(ListAuditLogs::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(AuditLog::all());
});

test('audit log view page renders properly', function () {
    $domain = $this->service->createZone(['name' => 'viewaudit.com']);
    $log = AuditLog::where('action', 'CREATE_ZONE')->where('model_id', $domain->id)->first();

    Livewire::test(ViewAuditLog::class, ['record' => $log->id])
        ->assertSuccessful();
});

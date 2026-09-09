<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\Customer;
use App\Models\DnsServer;
use App\Models\User;
use App\Services\PowerDNSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->superAdmin()->create(['email' => 'sa@example.com']);
    $this->dnsAdmin = User::factory()->dnsAdmin()->create(['email' => 'da@example.com']);
    $this->operator = User::factory()->operator()->create(['email' => 'op@example.com']);
    $this->customer = User::factory()->customer()->create(['email' => 'cust@example.com']);
});

test('super admin has full permissions on all models', function () {
    $targetUser = User::factory()->operator()->create();
    $domain = app(PowerDNSService::class)->createZone(['name' => 'superadmintest.com']);
    $customer = Customer::factory()->create();
    $server = DnsServer::create(['name' => 'NS1', 'hostname' => 'ns1.test.com', 'ip_address' => '1.1.1.1']);

    expect(Gate::forUser($this->superAdmin)->allows('viewAny', User::class))->toBeTrue()
        ->and(Gate::forUser($this->superAdmin)->allows('create', User::class))->toBeTrue()
        ->and(Gate::forUser($this->superAdmin)->allows('delete', $targetUser))->toBeTrue()
        ->and(Gate::forUser($this->superAdmin)->allows('delete', $domain))->toBeTrue()
        ->and(Gate::forUser($this->superAdmin)->allows('delete', $customer))->toBeTrue()
        ->and(Gate::forUser($this->superAdmin)->allows('delete', $server))->toBeTrue();
});

test('operator cannot delete users or zones', function () {
    $targetUser = User::factory()->customer()->create();
    $domain = app(PowerDNSService::class)->createZone(['name' => 'operatortest.com']);

    expect(Gate::forUser($this->operator)->allows('viewAny', User::class))->toBeTrue()
        ->and(Gate::forUser($this->operator)->allows('delete', $targetUser))->toBeFalse()
        ->and(Gate::forUser($this->operator)->allows('delete', $domain))->toBeFalse()
        ->and(Gate::forUser($this->operator)->allows('create', $domain))->toBeTrue();
});

test('dns admin can manage zones and customers but cannot delete users', function () {
    $targetUser = User::factory()->operator()->create();
    $domain = app(PowerDNSService::class)->createZone(['name' => 'dnsadmintest.com']);

    expect(Gate::forUser($this->dnsAdmin)->allows('create', $domain))->toBeTrue()
        ->and(Gate::forUser($this->dnsAdmin)->allows('delete', $domain))->toBeTrue()
        ->and(Gate::forUser($this->dnsAdmin)->allows('delete', $targetUser))->toBeFalse();
});

test('user list page can be rendered by super admin', function () {
    $this->actingAs($this->superAdmin);

    Livewire::test(ListUsers::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$this->superAdmin, $this->dnsAdmin, $this->operator]);
});

test('user create page creates a new user with hashed password', function () {
    $this->actingAs($this->superAdmin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'New Tech Admin',
            'email' => 'tech@isp.com',
            'role' => 'dns_admin',
            'password' => 'secret12345',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $newUser = User::where('email', 'tech@isp.com')->first();
    expect($newUser)->not->toBeNull()
        ->and($newUser->role)->toBe('dns_admin')
        ->and(Hash::check('secret12345', $newUser->password))->toBeTrue();
});

test('user edit page updates user details', function () {
    $this->actingAs($this->superAdmin);
    $user = User::factory()->operator()->create();

    Livewire::test(EditUser::class, ['record' => $user->id])
        ->fillForm([
            'role' => 'dns_admin',
            'is_active' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();
    expect($user->role)->toBe('dns_admin')
        ->and($user->is_active)->toBeFalse();
});

test('user view page renders properly', function () {
    $this->actingAs($this->superAdmin);

    Livewire::test(ViewUser::class, ['record' => $this->operator->id])
        ->assertSuccessful();
});

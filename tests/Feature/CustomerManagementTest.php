<?php

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Models\Customer;
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

test('customer list page can be rendered', function () {
    $customer = Customer::factory()->create(['name' => 'PT Nusantara Fiber']);

    Livewire::test(ListCustomers::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$customer]);
});

test('customer create page creates a new customer', function () {
    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'John Doe',
            'email' => 'john.doe@corporate.com',
            'phone' => '+628123456789',
            'company' => 'Corp ID',
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $customer = Customer::where('email', 'john.doe@corporate.com')->first();
    expect($customer)->not->toBeNull()
        ->and($customer->name)->toBe('John Doe')
        ->and($customer->company)->toBe('Corp ID');
});

test('customer edit page updates customer information', function () {
    $customer = Customer::factory()->create();

    Livewire::test(EditCustomer::class, ['record' => $customer->id])
        ->fillForm([
            'company' => 'Updated Company Name',
            'status' => 'suspended',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $customer->refresh();
    expect($customer->company)->toBe('Updated Company Name')
        ->and($customer->status)->toBe('suspended');
});

test('customer view page renders properly', function () {
    $customer = Customer::factory()->create();

    Livewire::test(ViewCustomer::class, ['record' => $customer->id])
        ->assertSuccessful();
});

test('customer zones relationship functions properly', function () {
    $customer = Customer::factory()->create();
    $service = app(PowerDNSService::class);

    $zone1 = $service->createZone([
        'name' => 'clientdomain1.com',
        'customer_id' => $customer->id,
    ]);

    $zone2 = $service->createZone([
        'name' => 'clientdomain2.com',
        'customer_id' => $customer->id,
    ]);

    expect($customer->zones()->count())->toBe(2)
        ->and($zone1->customer->id)->toBe($customer->id)
        ->and($zone2->customer->id)->toBe($customer->id);
});

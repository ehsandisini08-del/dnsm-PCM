<?php

use App\Models\Customer;
use App\Models\DnsServer;
use App\Models\PdnsDomain;
use App\Models\PdnsRecord;
use App\Models\User;
use App\Services\PowerDNSService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->superAdmin()->create([
        'email' => 'apiadmin@example.com',
        'password' => 'secret123',
    ]);

    $this->token = $this->admin->createToken('test-token')->plainTextToken;
    $this->authHeaders = [
        'Authorization' => "Bearer {$this->token}",
        'Accept' => 'application/json',
    ];
    $this->service = app(PowerDNSService::class);
});

// --- Auth Endpoints ---

test('api login returns bearer token with valid credentials', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => 'apiadmin@example.com',
        'password' => 'secret123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'token_type',
                'user' => ['id', 'name', 'email', 'role'],
            ],
        ]);
});

test('api login fails with invalid credentials', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => 'apiadmin@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
        ->assertJson(['success' => false]);
});

test('api me returns authenticated user profile', function () {
    $response = $this->getJson('/api/v1/me', $this->authHeaders);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'email' => 'apiadmin@example.com',
                'role' => 'super_admin',
            ],
        ]);
});

test('api logout revokes current token', function () {
    $token = $this->admin->createToken('logout-token')->plainTextToken;
    $headers = [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ];

    $response = $this->postJson('/api/v1/logout', [], $headers);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    expect($this->admin->tokens()->where('name', 'logout-token')->exists())->toBeFalse();
});

// --- Zones API ---

test('api can list all zones', function () {
    $this->service->createZone(['name' => 'apizone1.com']);
    $this->service->createZone(['name' => 'apizone2.com']);

    $response = $this->getJson('/api/v1/zones', $this->authHeaders);

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'links', 'meta'])
        ->assertJsonCount(2, 'data');
});

test('api can create a new zone', function () {
    $response = $this->postJson('/api/v1/zones', [
        'name' => 'newapizone.net',
        'type' => 'NATIVE',
    ], $this->authHeaders);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'name' => 'newapizone.net',
                'type' => 'NATIVE',
            ],
        ]);

    expect(PdnsDomain::where('name', 'newapizone.net')->exists())->toBeTrue();
});

test('api can get specific zone details', function () {
    $domain = $this->service->createZone(['name' => 'showzone.org']);

    $response = $this->getJson("/api/v1/zones/{$domain->id}", $this->authHeaders);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $domain->id,
                'name' => 'showzone.org',
            ],
        ]);
});

test('api can update zone details', function () {
    $domain = $this->service->createZone(['name' => 'updateapi.com']);

    $response = $this->putJson("/api/v1/zones/{$domain->id}", [
        'status' => 'disabled',
    ], $this->authHeaders);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'status' => 'disabled',
            ],
        ]);
});

test('api can delete zone', function () {
    $domain = $this->service->createZone(['name' => 'deleteapi.com']);

    $response = $this->deleteJson("/api/v1/zones/{$domain->id}", [], $this->authHeaders);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    expect(PdnsDomain::find($domain->id))->toBeNull();
});

// --- Records API ---

test('api can create a record in a zone', function () {
    $domain = $this->service->createZone(['name' => 'recordsapi.com']);

    $response = $this->postJson("/api/v1/zones/{$domain->id}/records", [
        'name' => 'www',
        'type' => 'A',
        'content' => '103.20.20.1',
        'ttl' => 1800,
    ], $this->authHeaders);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'name' => 'www.recordsapi.com',
                'type' => 'A',
                'content' => '103.20.20.1',
                'ttl' => 1800,
            ],
        ]);
});

test('api rejects creating manual SOA record', function () {
    $domain = $this->service->createZone(['name' => 'soablock.com']);

    $response = $this->postJson("/api/v1/zones/{$domain->id}/records", [
        'name' => '@',
        'type' => 'SOA',
        'content' => 'ns1.soablock.com hostmaster.soablock.com 2026090801 10800 3600 604800 3600',
    ], $this->authHeaders);

    $response->assertStatus(422);
});

test('api can list records for a zone', function () {
    $domain = $this->service->createZone(['name' => 'listrecords.com']);
    $this->service->createRecord($domain, [
        'name' => 'mail',
        'type' => 'A',
        'content' => '103.20.20.2',
    ]);

    $response = $this->getJson("/api/v1/zones/{$domain->id}/records", $this->authHeaders);

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);

    // SOA, NS records, and the newly added A record
    expect(count($response->json('data')))->toBeGreaterThanOrEqual(3);
});

test('api can update a record', function () {
    $domain = $this->service->createZone(['name' => 'editrecord.com']);
    $record = $this->service->createRecord($domain, [
        'name' => 'api',
        'type' => 'A',
        'content' => '103.20.20.5',
    ]);

    $response = $this->putJson("/api/v1/records/{$record->id}", [
        'content' => '103.20.20.6',
    ], $this->authHeaders);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'content' => '103.20.20.6',
            ],
        ]);
});

test('api can delete a record', function () {
    $domain = $this->service->createZone(['name' => 'delrecord.com']);
    $record = $this->service->createRecord($domain, [
        'name' => 'staging',
        'type' => 'A',
        'content' => '103.20.20.10',
    ]);

    $response = $this->deleteJson("/api/v1/records/{$record->id}", [], $this->authHeaders);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    expect(PdnsRecord::find($record->id))->toBeNull();
});

// --- DNS Servers & Customers API ---

test('api can list dns servers', function () {
    DnsServer::factory()->count(2)->create();

    $response = $this->getJson('/api/v1/dns-servers', $this->authHeaders);

    $response->assertStatus(200)
        ->assertJsonStructure(['data']);
});

test('api can list customers', function () {
    Customer::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/customers', $this->authHeaders);

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'links', 'meta']);
});

// --- Customer Role Isolation ---

test('customer token only sees their assigned zones', function () {
    $customer1 = Customer::factory()->create();
    $customer2 = Customer::factory()->create();

    $zone1 = $this->service->createZone(['name' => 'cust1zone.com', 'customer_id' => $customer1->id]);
    $zone2 = $this->service->createZone(['name' => 'cust2zone.com', 'customer_id' => $customer2->id]);

    $custUser = User::factory()->customer()->create([
        'customer_id' => $customer1->id,
    ]);
    $custToken = $custUser->createToken('cust-token')->plainTextToken;
    $custHeaders = [
        'Authorization' => "Bearer {$custToken}",
        'Accept' => 'application/json',
    ];

    // Listing should only return zone1
    $response = $this->getJson('/api/v1/zones', $custHeaders);
    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'cust1zone.com');

    // Accessing zone2 should return 403 Forbidden
    $this->getJson("/api/v1/zones/{$zone2->id}", $custHeaders)
        ->assertStatus(403);
});

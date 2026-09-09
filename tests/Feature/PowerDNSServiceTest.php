<?php

use App\Exceptions\PowerDNSException;
use App\Models\Customer;
use App\Models\DnsServer;
use App\Models\PdnsDomain;
use App\Models\PdnsRecord;
use App\Services\PowerDNSService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(PowerDNSService::class);
});

test('service can create zone with default soa and ns records', function () {
    $domain = $this->service->createZone([
        'name' => 'example.com',
        'type' => 'NATIVE',
    ]);

    expect($domain)->toBeInstanceOf(PdnsDomain::class)
        ->and($domain->name)->toBe('example.com')
        ->and($domain->type)->toBe('NATIVE')
        ->and($domain->sync_status)->toBe('synced');

    // Verify SOA record
    $soa = $domain->records()->where('type', 'SOA')->first();
    expect($soa)->not->toBeNull()
        ->and($soa->name)->toBe('example.com');

    // Verify NS records
    $nsCount = $domain->records()->where('type', 'NS')->count();
    expect($nsCount)->toBeGreaterThanOrEqual(1);
});

test('service prevents creating duplicate zones', function () {
    $this->service->createZone(['name' => 'example.com']);

    $this->expectException(PowerDNSException::class);
    $this->service->createZone(['name' => 'example.com.']);
});

test('service can update zone details', function () {
    $domain = $this->service->createZone(['name' => 'example.com']);
    $customer = Customer::create([
        'name' => 'ISP Customer 1',
        'email' => 'customer1@example.com',
    ]);

    $updated = $this->service->updateZone($domain, [
        'type' => 'MASTER',
        'customer_id' => $customer->id,
    ]);

    expect($updated->type)->toBe('MASTER')
        ->and($updated->customer_id)->toBe($customer->id);
});

test('service can delete zone and all associated records', function () {
    $domain = $this->service->createZone(['name' => 'example.com']);
    $this->service->createRecord($domain, [
        'name' => 'www',
        'type' => 'A',
        'content' => '103.10.10.1',
    ]);

    $domainId = $domain->id;
    $deleted = $this->service->deleteZone($domain);

    expect($deleted)->toBeTrue()
        ->and(PdnsDomain::find($domainId))->toBeNull()
        ->and(PdnsRecord::where('domain_id', $domainId)->count())->toBe(0);
});

test('service can create record and automatically increment soa serial', function () {
    $domain = $this->service->createZone(['name' => 'example.com']);
    $initialSoa = $domain->soaRecord()->first()->content;

    $record = $this->service->createRecord($domain, [
        'name' => 'www',
        'type' => 'A',
        'content' => '103.10.10.1',
        'ttl' => 3600,
    ]);

    expect($record)->toBeInstanceOf(PdnsRecord::class)
        ->and($record->name)->toBe('www.example.com')
        ->and($record->type)->toBe('A')
        ->and($record->content)->toBe('103.10.10.1');

    $updatedSoa = $domain->soaRecord()->first()->content;
    expect($updatedSoa)->not->toBe($initialSoa);
});

test('service qualifies root and subdomain record names properly', function () {
    $domain = $this->service->createZone(['name' => 'example.com']);

    $rootRecord = $this->service->createRecord($domain, [
        'name' => '@',
        'type' => 'A',
        'content' => '103.10.10.1',
    ]);

    $subRecord = $this->service->createRecord($domain, [
        'name' => 'mail.example.com',
        'type' => 'A',
        'content' => '103.10.10.2',
    ]);

    expect($rootRecord->name)->toBe('example.com')
        ->and($subRecord->name)->toBe('mail.example.com');
});

test('service rejects unsupported record types', function () {
    $domain = $this->service->createZone(['name' => 'example.com']);

    $this->expectException(PowerDNSException::class);
    $this->service->createRecord($domain, [
        'name' => 'test',
        'type' => 'INVALID_TYPE',
        'content' => 'test',
    ]);
});

test('service can update and delete record', function () {
    $domain = $this->service->createZone(['name' => 'example.com']);
    $record = $this->service->createRecord($domain, [
        'name' => 'www',
        'type' => 'A',
        'content' => '103.10.10.1',
    ]);

    $updated = $this->service->updateRecord($record, [
        'content' => '103.10.10.2',
    ]);

    expect($updated->content)->toBe('103.10.10.2');

    $deleted = $this->service->deleteRecord($updated);
    expect($deleted)->toBeTrue()
        ->and(PdnsRecord::find($record->id))->toBeNull();
});

test('service prevents deleting soa record', function () {
    $domain = $this->service->createZone(['name' => 'example.com']);
    $soa = $domain->soaRecord()->first();

    $this->expectException(PowerDNSException::class);
    $this->service->deleteRecord($soa);
});

test('domain and customer and dns_server relationships work correctly', function () {
    $customer = Customer::create([
        'name' => 'PT Pelangi Nusantara',
        'email' => 'noc@pelangi.net',
    ]);

    $server = DnsServer::create([
        'name' => 'NS1 Primary',
        'hostname' => 'ns1.pelangi.net',
        'ip_address' => '103.10.10.53',
        'type' => 'authoritative',
        'status' => 'online',
    ]);

    $domain = $this->service->createZone([
        'name' => 'pelangicomm.net',
        'customer_id' => $customer->id,
        'dns_server_id' => $server->id,
    ]);

    expect($domain->customer->id)->toBe($customer->id)
        ->and($domain->dnsServer->id)->toBe($server->id)
        ->and($customer->zones()->first()->name)->toBe('pelangicomm.net')
        ->and($server->zones()->first()->name)->toBe('pelangicomm.net');
});

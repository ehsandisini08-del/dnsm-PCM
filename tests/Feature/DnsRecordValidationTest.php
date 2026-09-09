<?php

use App\Services\DnsRecordValidator;
use App\Services\PowerDNSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(PowerDNSService::class);
    $this->domain = $this->service->createZone(['name' => 'example.com']);
});

// --- A Record ---

test('valid A record is accepted', function () {
    $record = $this->service->createRecord($this->domain, [
        'name' => 'www',
        'type' => 'A',
        'content' => '103.10.10.1',
    ]);
    expect($record->type)->toBe('A')
        ->and($record->content)->toBe('103.10.10.1');
});

test('invalid A record IPv6 is rejected', function () {
    $this->service->createRecord($this->domain, [
        'name' => 'www',
        'type' => 'A',
        'content' => '2001:db8::1',
    ]);
})->throws(ValidationException::class);

test('invalid A record text is rejected', function () {
    $this->service->createRecord($this->domain, [
        'name' => 'www',
        'type' => 'A',
        'content' => 'not-an-ip',
    ]);
})->throws(ValidationException::class);

// --- AAAA Record ---

test('valid AAAA record is accepted', function () {
    $record = $this->service->createRecord($this->domain, [
        'name' => 'ipv6',
        'type' => 'AAAA',
        'content' => '2001:db8::1',
    ]);
    expect($record->type)->toBe('AAAA');
});

test('invalid AAAA record IPv4 is rejected', function () {
    $this->service->createRecord($this->domain, [
        'name' => 'ipv6',
        'type' => 'AAAA',
        'content' => '103.10.10.1',
    ]);
})->throws(ValidationException::class);

// --- CNAME Record ---

test('valid CNAME record is accepted and normalized', function () {
    $record = $this->service->createRecord($this->domain, [
        'name' => 'alias',
        'type' => 'CNAME',
        'content' => 'target.example.com',
    ]);
    expect($record->content)->toBe('target.example.com.');
});

test('CNAME with IP address is rejected', function () {
    $this->service->createRecord($this->domain, [
        'name' => 'alias',
        'type' => 'CNAME',
        'content' => '103.10.10.1',
    ]);
})->throws(ValidationException::class);

// --- MX Record ---

test('valid MX record is accepted', function () {
    $record = $this->service->createRecord($this->domain, [
        'name' => '@',
        'type' => 'MX',
        'content' => 'mail.example.com',
        'prio' => 10,
    ]);
    expect($record->prio)->toBe(10)
        ->and($record->content)->toBe('mail.example.com.');
});

test('MX record without priority is rejected', function () {
    $this->service->createRecord($this->domain, [
        'name' => '@',
        'type' => 'MX',
        'content' => 'mail.example.com',
    ]);
})->throws(ValidationException::class);

test('MX record with IP as target is rejected', function () {
    $this->service->createRecord($this->domain, [
        'name' => '@',
        'type' => 'MX',
        'content' => '103.10.10.1',
        'prio' => 10,
    ]);
})->throws(ValidationException::class);

// --- TXT Record ---

test('valid TXT SPF record is accepted', function () {
    $record = $this->service->createRecord($this->domain, [
        'name' => '@',
        'type' => 'TXT',
        'content' => 'v=spf1 include:_spf.google.com ~all',
    ]);
    expect($record->type)->toBe('TXT');
});

// --- SRV Record ---

test('valid SRV record is accepted', function () {
    $record = $this->service->createRecord($this->domain, [
        'name' => '_sip._tcp',
        'type' => 'SRV',
        'content' => '10 5060 sip.example.com',
        'prio' => 5,
    ]);
    expect($record->type)->toBe('SRV')
        ->and($record->prio)->toBe(5);
});

test('SRV with invalid content format is rejected', function () {
    $this->service->createRecord($this->domain, [
        'name' => '_sip._tcp',
        'type' => 'SRV',
        'content' => 'missing parts',
        'prio' => 5,
    ]);
})->throws(ValidationException::class);

// --- CAA Record ---

test('valid CAA record is accepted and normalized', function () {
    $record = $this->service->createRecord($this->domain, [
        'name' => '@',
        'type' => 'CAA',
        'content' => '0 issue letsencrypt.org',
    ]);
    expect($record->content)->toBe('0 issue "letsencrypt.org"');
});

test('CAA with invalid tag is rejected', function () {
    $this->service->createRecord($this->domain, [
        'name' => '@',
        'type' => 'CAA',
        'content' => '0 badtag "letsencrypt.org"',
    ]);
})->throws(ValidationException::class);

// --- PTR Record ---

test('valid PTR record is accepted and normalized', function () {
    $record = $this->service->createRecord($this->domain, [
        'name' => '1',
        'type' => 'PTR',
        'content' => 'host.example.com',
    ]);
    expect($record->content)->toBe('host.example.com.');
});

// --- NS Record ---

test('valid NS record is accepted and normalized', function () {
    $record = $this->service->createRecord($this->domain, [
        'name' => '@',
        'type' => 'NS',
        'content' => 'ns3.example.com',
    ]);
    expect($record->content)->toBe('ns3.example.com.');
});

// --- Content normalization ---

test('normalizeContent wraps TXT in quotes', function () {
    $result = DnsRecordValidator::normalizeContent('TXT', 'v=spf1 ~all');
    expect($result)->toBe('"v=spf1 ~all"');
});

test('normalizeContent adds trailing dot to CNAME', function () {
    expect(DnsRecordValidator::normalizeContent('CNAME', 'target.example.com'))->toBe('target.example.com.');
    expect(DnsRecordValidator::normalizeContent('CNAME', 'target.example.com.'))->toBe('target.example.com.');
});

test('normalizeContent formats CAA properly', function () {
    $result = DnsRecordValidator::normalizeContent('CAA', '0 issue letsencrypt.org');
    expect($result)->toBe('0 issue "letsencrypt.org"');
});

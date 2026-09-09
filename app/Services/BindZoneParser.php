<?php

namespace App\Services;

use App\Exceptions\PowerDNSException;
use App\Models\PdnsDomain;
use App\Models\PdnsRecord;

class BindZoneParser
{
    /**
     * Parse a BIND zone file content string into a structured array.
     *
     * @return array{origin: string|null, ttl: int, records: array<int, array{name: string, type: string, content: string, ttl: int, prio: int|null}>}
     */
    public static function parse(string $content): array
    {
        $lines = preg_split('/\r?\n/', $content);
        $origin = null;
        $defaultTtl = 3600;
        $records = [];
        $lastName = '@';

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, ';')) {
                continue;
            }

            $line = preg_replace('/\s*;.*$/', '', $line);

            if (preg_match('/^\$ORIGIN\s+(\S+)/i', $line, $m)) {
                $origin = rtrim($m[1], '.');

                continue;
            }

            if (preg_match('/^\$TTL\s+(\d+)/i', $line, $m)) {
                $defaultTtl = (int) $m[1];

                continue;
            }

            if (preg_match('/^\$/', $line)) {
                continue;
            }

            $parsed = self::parseRecordLine($line, $defaultTtl, $lastName);
            if ($parsed) {
                $lastName = $parsed['name'];
                $records[] = $parsed;
            }
        }

        return [
            'origin' => $origin,
            'ttl' => $defaultTtl,
            'records' => $records,
        ];
    }

    /**
     * Parse a single record line.
     *
     * @return array{name: string, type: string, content: string, ttl: int, prio: int|null}|null
     */
    protected static function parseRecordLine(string $line, int $defaultTtl, string $lastName): ?array
    {
        $validTypes = ['SOA', 'A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SRV', 'CAA', 'PTR'];

        $tokens = preg_split('/\s+/', $line);
        if (count($tokens) < 3) {
            return null;
        }

        $name = null;
        $ttl = $defaultTtl;
        $class = null;
        $type = null;
        $prio = null;
        $contentTokens = [];

        $idx = 0;
        $total = count($tokens);

        // Token 1: Name or continuation
        if (! is_numeric($tokens[0]) && ! in_array(strtoupper($tokens[0]), ['IN', ...$validTypes], true)) {
            $name = $tokens[0];
            $idx++;
        } else {
            $name = $lastName;
        }

        // Token 2: Optional TTL
        if ($idx < $total && is_numeric($tokens[$idx]) && ! in_array(strtoupper($tokens[$idx]), $validTypes, true)) {
            $ttl = (int) $tokens[$idx];
            $idx++;
        }

        // Token 3: Optional class (IN)
        if ($idx < $total && strtoupper($tokens[$idx]) === 'IN') {
            $class = 'IN';
            $idx++;
        }

        // Token 4: Type
        if ($idx < $total && in_array(strtoupper($tokens[$idx]), $validTypes, true)) {
            $type = strtoupper($tokens[$idx]);
            $idx++;
        } else {
            return null;
        }

        // Remaining tokens: content
        $contentTokens = array_slice($tokens, $idx);

        if (empty($contentTokens)) {
            return null;
        }

        // Handle MX priority
        if ($type === 'MX' && count($contentTokens) >= 2 && is_numeric($contentTokens[0])) {
            $prio = (int) $contentTokens[0];
            $contentTokens = array_slice($contentTokens, 1);
        }

        // Handle SRV priority (priority is separate in BIND, weight port target in content)
        if ($type === 'SRV' && count($contentTokens) >= 4 && is_numeric($contentTokens[0])) {
            $prio = (int) $contentTokens[0];
            $contentTokens = array_slice($contentTokens, 1);
        }

        $content = implode(' ', $contentTokens);

        // Handle TXT records with quoted strings
        if ($type === 'TXT') {
            $fullLine = implode(' ', array_slice($tokens, $idx));
            if (preg_match('/"(.+)"/', $fullLine, $txtMatch)) {
                $content = $txtMatch[1];
            }
        }

        // Handle SOA (multi-token content)
        if ($type === 'SOA') {
            $content = implode(' ', $contentTokens);
            $content = str_replace(['(', ')'], '', $content);
            $content = trim(preg_replace('/\s+/', ' ', $content));
        }

        return [
            'name' => $name,
            'type' => $type,
            'content' => $content,
            'ttl' => $ttl,
            'prio' => $prio,
        ];
    }

    /**
     * Import parsed BIND zone data into a new or existing PdnsDomain.
     *
     * @param  array{origin: string|null, ttl: int, records: array<int, array{name: string, type: string, content: string, ttl: int, prio: int|null}>}  $parsed
     *
     * @throws PowerDNSException
     */
    public static function import(array $parsed, ?string $zoneName = null, ?int $customerId = null, ?int $dnsServerId = null): PdnsDomain
    {
        $name = $zoneName ?? $parsed['origin'];

        if (empty($name)) {
            throw new PowerDNSException('Zone name could not be determined from BIND zone file. Please specify a zone name.');
        }

        $service = app(PowerDNSService::class);
        $name = $service->normalizeZoneName($name);

        $existingDomain = PdnsDomain::where('name', $name)->first();

        if ($existingDomain) {
            $domain = $existingDomain;
        } else {
            $domain = PdnsDomain::create([
                'name' => $name,
                'type' => 'NATIVE',
                'customer_id' => $customerId,
                'dns_server_id' => $dnsServerId,
                'status' => 'active',
                'sync_status' => 'synced',
            ]);
        }

        foreach ($parsed['records'] as $rec) {
            if ($rec['type'] === 'SOA' && $existingDomain) {
                $existingSoa = $domain->records()->where('type', 'SOA')->first();
                if ($existingSoa) {
                    $existingSoa->update(['content' => $rec['content'], 'ttl' => $rec['ttl']]);

                    continue;
                }
            }

            $qualifiedName = $service->qualifyRecordName($rec['name'], $name);

            PdnsRecord::create([
                'domain_id' => $domain->id,
                'name' => $qualifiedName,
                'type' => $rec['type'],
                'content' => $rec['content'],
                'ttl' => $rec['ttl'],
                'prio' => $rec['prio'],
                'auth' => true,
                'disabled' => false,
            ]);
        }

        $domain->update(['sync_status' => 'pending']);

        return $domain->fresh(['records']);
    }

    /**
     * Import from JSON format.
     *
     * @param  array{name: string, type?: string, records: array<int, array{name: string, type: string, content: string, ttl?: int, prio?: int|null, disabled?: bool}>}  $data
     *
     * @throws PowerDNSException
     */
    public static function importJson(array $data, ?int $customerId = null, ?int $dnsServerId = null): PdnsDomain
    {
        $service = app(PowerDNSService::class);
        $name = $service->normalizeZoneName($data['name'] ?? '');

        if (empty($name)) {
            throw new PowerDNSException('Zone name is required in JSON import data.');
        }

        $existingDomain = PdnsDomain::where('name', $name)->first();

        if ($existingDomain) {
            $domain = $existingDomain;
        } else {
            $domain = PdnsDomain::create([
                'name' => $name,
                'type' => strtoupper($data['type'] ?? 'NATIVE'),
                'customer_id' => $customerId,
                'dns_server_id' => $dnsServerId,
                'status' => 'active',
                'sync_status' => 'synced',
            ]);
        }

        foreach ($data['records'] ?? [] as $rec) {
            $qualifiedName = $service->qualifyRecordName($rec['name'] ?? '@', $name);

            if (strtoupper($rec['type']) === 'SOA' && $existingDomain) {
                $existingSoa = $domain->records()->where('type', 'SOA')->first();
                if ($existingSoa) {
                    $existingSoa->update(['content' => $rec['content'], 'ttl' => $rec['ttl'] ?? 3600]);

                    continue;
                }
            }

            PdnsRecord::create([
                'domain_id' => $domain->id,
                'name' => $qualifiedName,
                'type' => strtoupper($rec['type']),
                'content' => $rec['content'],
                'ttl' => (int) ($rec['ttl'] ?? 3600),
                'prio' => isset($rec['prio']) ? (int) $rec['prio'] : null,
                'disabled' => (bool) ($rec['disabled'] ?? false),
                'auth' => true,
            ]);
        }

        $domain->update(['sync_status' => 'pending']);

        return $domain->fresh(['records']);
    }
}

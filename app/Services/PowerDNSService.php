<?php

namespace App\Services;

use App\Exceptions\PowerDNSException;
use App\Models\DnsServer;
use App\Models\DnsTemplate;
use App\Models\PdnsDomain;
use App\Models\PdnsRecord;
use App\Models\ServerHealthCheck;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PowerDNSService
{
    /**
     * Supported DNS Record Types.
     */
    public const SUPPORTED_TYPES = [
        'A',
        'AAAA',
        'CNAME',
        'MX',
        'TXT',
        'NS',
        'SRV',
        'CAA',
        'PTR',
        'SOA',
    ];

    /**
     * Create a new DNS Zone with default SOA and NS records.
     *
     * @param  array{name: string, type?: string, customer_id?: int|null, dns_server_id?: int|null, custom_ns?: array<string>|null}  $data
     *
     * @throws PowerDNSException
     */
    public function createZone(array $data): PdnsDomain
    {
        $zoneName = $this->normalizeZoneName($data['name']);

        if (PdnsDomain::where('name', $zoneName)->exists()) {
            throw new PowerDNSException("DNS Zone [{$zoneName}] already exists.");
        }

        try {
            return DB::transaction(function () use ($zoneName, $data) {
                $domain = PdnsDomain::create([
                    'name' => $zoneName,
                    'type' => strtoupper($data['type'] ?? 'NATIVE'),
                    'customer_id' => $data['customer_id'] ?? null,
                    'dns_server_id' => $data['dns_server_id'] ?? null,
                    'status' => 'active',
                    'sync_status' => 'synced',
                ]);

                // 1. Create Default SOA Record
                $serial = $this->generateInitialSerial();
                $soaContent = $this->generateSoaContent($zoneName, $serial);

                PdnsRecord::create([
                    'domain_id' => $domain->id,
                    'name' => $zoneName,
                    'type' => 'SOA',
                    'content' => $soaContent,
                    'ttl' => (int) config('powerdns.defaults.ttl', 3600),
                    'auth' => true,
                ]);

                // 2. Create Default NS Records
                $nameservers = (! empty($data['custom_ns'])) ? $data['custom_ns'] : (config('powerdns.defaults.nameservers') ?: ['ns1.example.com', 'ns2.example.com']);

                foreach ($nameservers as $ns) {
                    if (! empty(trim($ns))) {
                        PdnsRecord::create([
                            'domain_id' => $domain->id,
                            'name' => $zoneName,
                            'type' => 'NS',
                            'content' => $this->normalizeFqdn($ns),
                            'ttl' => (int) config('powerdns.defaults.ttl', 3600),
                            'auth' => true,
                        ]);
                    }
                }

                return $domain->fresh(['records']);
            });
        } catch (Throwable $e) {
            Log::error("PowerDNS: Failed to create zone {$zoneName}: ".$e->getMessage(), ['exception' => $e]);
            throw PowerDNSException::databaseError("Failed to create DNS Zone [{$zoneName}]: ".$e->getMessage(), $e);
        }
    }

    /**
     * Update DNS Zone details.
     *
     * @param  array{type?: string, customer_id?: int|null, dns_server_id?: int|null, status?: string}  $data
     *
     * @throws PowerDNSException
     */
    public function updateZone(PdnsDomain|int $domain, array $data): PdnsDomain
    {
        $domainModel = $this->resolveDomain($domain);

        try {
            if (isset($data['type'])) {
                $data['type'] = strtoupper($data['type']);
            }

            $domainModel->update($data);

            return $domainModel->fresh();
        } catch (Throwable $e) {
            Log::error("PowerDNS: Failed to update zone {$domainModel->name}: ".$e->getMessage(), ['exception' => $e]);
            throw PowerDNSException::databaseError('Failed to update DNS Zone: '.$e->getMessage(), $e);
        }
    }

    /**
     * Delete a DNS Zone along with all associated records.
     *
     * @throws PowerDNSException
     */
    public function deleteZone(PdnsDomain|int $domain): bool
    {
        $domainModel = $this->resolveDomain($domain);

        try {
            return (bool) DB::transaction(function () use ($domainModel) {
                return $domainModel->delete();
            });
        } catch (Throwable $e) {
            Log::error("PowerDNS: Failed to delete zone {$domainModel->name}: ".$e->getMessage(), ['exception' => $e]);
            throw PowerDNSException::databaseError('Failed to delete DNS Zone: '.$e->getMessage(), $e);
        }
    }

    /**
     * Get a Zone by ID or Name.
     */
    public function getZone(int|string $idOrName): ?PdnsDomain
    {
        if (is_numeric($idOrName)) {
            return PdnsDomain::with(['records', 'customer', 'dnsServer'])->find((int) $idOrName);
        }

        $normalized = $this->normalizeZoneName($idOrName);

        return PdnsDomain::with(['records', 'customer', 'dnsServer'])->where('name', $normalized)->first();
    }

    /**
     * Get records for a specific zone, optionally filtered by type.
     *
     * @return Collection<int, PdnsRecord>
     *
     * @throws PowerDNSException
     */
    public function getRecords(PdnsDomain|int $domain, ?string $type = null): Collection
    {
        $domainModel = $this->resolveDomain($domain);

        $query = $domainModel->records();

        if ($type !== null) {
            $query->where('type', strtoupper($type));
        }

        return $query->get();
    }

    /**
     * Create a new DNS Record in a zone and automatically increment the SOA serial.
     *
     * @param  array{name: string, type: string, content: string, ttl?: int, prio?: int|null, disabled?: bool, auth?: bool}  $data
     *
     * @throws PowerDNSException
     */
    public function createRecord(PdnsDomain|int $domain, array $data): PdnsRecord
    {
        $domainModel = $this->resolveDomain($domain);
        $type = strtoupper($data['type'] ?? '');

        if (! in_array($type, self::SUPPORTED_TYPES, true)) {
            throw PowerDNSException::invalidRecordType($type);
        }

        // Validate record content & structure
        DnsRecordValidator::validate($data);

        $qualifiedName = $this->qualifyRecordName($data['name'], $domainModel->name);
        $normalizedContent = DnsRecordValidator::normalizeContent($type, $data['content']);

        try {
            return DB::transaction(function () use ($domainModel, $qualifiedName, $type, $normalizedContent, $data) {
                $record = PdnsRecord::create([
                    'domain_id' => $domainModel->id,
                    'name' => $qualifiedName,
                    'type' => $type,
                    'content' => $normalizedContent,
                    'ttl' => (int) ($data['ttl'] ?? config('powerdns.defaults.ttl', 3600)),
                    'prio' => isset($data['prio']) && $data['prio'] !== '' ? (int) $data['prio'] : null,
                    'disabled' => (bool) ($data['disabled'] ?? false),
                    'auth' => (bool) ($data['auth'] ?? true),
                ]);

                if ($type !== 'SOA') {
                    $this->incrementSoaSerial($domainModel);
                }

                $domainModel->update(['sync_status' => 'synced', 'sync_error' => null]);

                return $record;
            });
        } catch (Throwable $e) {
            Log::error("PowerDNS: Failed to create record for {$domainModel->name}: ".$e->getMessage(), ['exception' => $e]);
            throw PowerDNSException::databaseError('Failed to create DNS Record: '.$e->getMessage(), $e);
        }
    }

    /**
     * Update an existing DNS Record and increment SOA serial.
     *
     * @param  array{name?: string, type?: string, content?: string, ttl?: int, prio?: int|null, disabled?: bool, auth?: bool}  $data
     *
     * @throws PowerDNSException
     */
    public function updateRecord(PdnsRecord|int $record, array $data): PdnsRecord
    {
        $recordModel = $this->resolveRecord($record);
        $domainModel = $recordModel->domain;

        if (! $domainModel) {
            throw PowerDNSException::zoneNotFound($recordModel->domain_id);
        }

        $type = isset($data['type']) ? strtoupper($data['type']) : $recordModel->type;
        if (! in_array($type, self::SUPPORTED_TYPES, true)) {
            throw PowerDNSException::invalidRecordType($type);
        }

        // Prepare merged data for validation
        $mergedForValidation = array_merge([
            'name' => $recordModel->name,
            'type' => $recordModel->type,
            'content' => $recordModel->content,
            'ttl' => $recordModel->ttl,
            'prio' => $recordModel->prio,
        ], $data);

        DnsRecordValidator::validate($mergedForValidation);

        if (isset($data['name'])) {
            $data['name'] = $this->qualifyRecordName($data['name'], $domainModel->name);
        }

        if (isset($data['content'])) {
            $data['content'] = DnsRecordValidator::normalizeContent($type, $data['content']);
        }

        if (isset($data['type'])) {
            $data['type'] = $type;
        }

        try {
            return DB::transaction(function () use ($recordModel, $domainModel, $data) {
                $recordModel->update($data);

                if ($recordModel->type !== 'SOA') {
                    $this->incrementSoaSerial($domainModel);
                }

                $domainModel->update(['sync_status' => 'synced', 'sync_error' => null]);

                return $recordModel->fresh();
            });
        } catch (Throwable $e) {
            Log::error("PowerDNS: Failed to update record ID {$recordModel->id}: ".$e->getMessage(), ['exception' => $e]);
            throw PowerDNSException::databaseError('Failed to update DNS Record: '.$e->getMessage(), $e);
        }
    }

    /**
     * Delete a DNS Record and increment SOA serial.
     *
     * @throws PowerDNSException
     */
    public function deleteRecord(PdnsRecord|int $record): bool
    {
        $recordModel = $this->resolveRecord($record);
        $domainModel = $recordModel->domain;

        if (! $domainModel) {
            throw PowerDNSException::zoneNotFound($recordModel->domain_id);
        }

        if ($recordModel->type === 'SOA') {
            throw new PowerDNSException('Cannot delete the SOA record of a zone.');
        }

        try {
            return (bool) DB::transaction(function () use ($recordModel, $domainModel) {
                $deleted = $recordModel->delete();

                $this->incrementSoaSerial($domainModel);
                $domainModel->update(['sync_status' => 'synced', 'sync_error' => null]);

                return $deleted;
            });
        } catch (Throwable $e) {
            Log::error("PowerDNS: Failed to delete record ID {$recordModel->id}: ".$e->getMessage(), ['exception' => $e]);
            throw PowerDNSException::databaseError('Failed to delete DNS Record: '.$e->getMessage(), $e);
        }
    }

    /**
     * Increment the SOA record serial for a zone.
     *
     * @throws PowerDNSException
     */
    public function incrementSoaSerial(PdnsDomain|int $domain): int
    {
        $domainModel = $this->resolveDomain($domain);
        $soaRecord = $domainModel->soaRecord()->first();

        if (! $soaRecord) {
            // Create a fresh SOA record if missing
            $newSerial = $this->generateInitialSerial();
            PdnsRecord::create([
                'domain_id' => $domainModel->id,
                'name' => $domainModel->name,
                'type' => 'SOA',
                'content' => $this->generateSoaContent($domainModel->name, $newSerial),
                'ttl' => (int) config('powerdns.defaults.ttl', 3600),
                'auth' => true,
            ]);

            return $newSerial;
        }

        $parts = preg_split('/\s+/', trim($soaRecord->content));
        if (count($parts) < 7) {
            $newSerial = $this->generateInitialSerial();
            $soaRecord->update([
                'content' => $this->generateSoaContent($domainModel->name, $newSerial),
            ]);

            return $newSerial;
        }

        $currentSerial = (int) $parts[2];
        $todayPrefix = (int) date('Ymd');
        $currentPrefix = (int) substr((string) $currentSerial, 0, 8);

        if ($currentPrefix === $todayPrefix) {
            $newSerial = $currentSerial + 1;
        } else {
            $newSerial = (int) ($todayPrefix.'01');
        }

        $parts[2] = (string) $newSerial;
        $soaRecord->update([
            'content' => implode(' ', $parts),
        ]);

        return $newSerial;
    }

    /**
     * Synchronize a zone (verify consistency and notify via API if configured).
     *
     * @throws PowerDNSException
     */
    public function syncZone(PdnsDomain|int $domain): bool
    {
        $domainModel = $this->resolveDomain($domain);

        try {
            // 1. Consistency check: Zone must have SOA
            $hasSoa = $domainModel->records()->where('type', 'SOA')->exists();
            if (! $hasSoa) {
                $this->incrementSoaSerial($domainModel);
            }

            // 2. Consistency check: Zone must have at least 1 NS
            $hasNs = $domainModel->records()->where('type', 'NS')->exists();
            if (! $hasNs) {
                $defaultNs = config('powerdns.defaults.nameservers', ['ns1.example.com']);
                foreach ($defaultNs as $ns) {
                    PdnsRecord::create([
                        'domain_id' => $domainModel->id,
                        'name' => $domainModel->name,
                        'type' => 'NS',
                        'content' => $this->normalizeFqdn($ns),
                        'ttl' => (int) config('powerdns.defaults.ttl', 3600),
                        'auth' => true,
                    ]);
                }
            }

            // 3. Optional HTTP API Notify
            $apiUrl = config('powerdns.api.url');
            $apiKey = config('powerdns.api.key');
            $serverId = config('powerdns.api.server_id', 'localhost');

            if (! empty($apiUrl) && ! empty($apiKey)) {
                $response = Http::timeout((int) config('powerdns.api.timeout', 5))
                    ->withHeaders(['X-API-Key' => $apiKey])
                    ->put(rtrim($apiUrl, '/')."/api/v1/servers/{$serverId}/zones/{$domainModel->name}/notify");

                if (! $response->successful() && $response->status() !== 404) {
                    throw new PowerDNSException('PowerDNS API notify returned HTTP '.$response->status());
                }
            }

            $domainModel->update([
                'sync_status' => 'synced',
                'sync_error' => null,
            ]);

            return true;
        } catch (Throwable $e) {
            $domainModel->update([
                'sync_status' => 'error',
                'sync_error' => $e->getMessage(),
            ]);

            Log::warning("PowerDNS: Sync error for {$domainModel->name}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Check DNS Server Health (Port 53 TCP/UDP connectivity, DNS Query check & API connectivity).
     *
     * @return array{status: string, latency_ms: float|null, details: array<string, mixed>}
     */
    public function checkServerHealth(DnsServer $server): array
    {
        $startTime = microtime(true);
        $port = $server->port ?: 53;
        $ip = $server->ip_address;
        $tcpOnline = false;
        $udpOnline = false;
        $dnsQueryOnline = false;
        $apiOnline = null;
        $errorMessage = null;

        $ipsToTry = array_unique(array_filter([$ip, '127.0.0.1']));

        // 1. Test Port 53 TCP socket
        foreach ($ipsToTry as $testIp) {
            $connection = @fsockopen($testIp, $port, $errno, $errstr, 2);
            if (is_resource($connection)) {
                $tcpOnline = true;
                fclose($connection);
                break;
            }
        }
        if (! $tcpOnline) {
            $errorMessage = $errstr ?: 'TCP Port connection timed out';
        }

        // 2. Test Port 53 UDP Socket with actual DNS probe
        foreach ($ipsToTry as $testIp) {
            $udpSocket = @fsockopen("udp://{$testIp}", $port, $errno, $errstr, 1);
            if (is_resource($udpSocket)) {
                stream_set_timeout($udpSocket, 1);
                $dnsQuery = "\xaa\xaa\x01\x00\x00\x01\x00\x00\x00\x00\x00\x00\x07example\x03com\x00\x00\x01\x00\x01";
                @fwrite($udpSocket, $dnsQuery);
                $response = @fread($udpSocket, 512);
                if ($response && strlen($response) >= 12) {
                    $udpOnline = true;
                    $dnsQueryOnline = true;
                    fclose($udpSocket);
                    break;
                }
                fclose($udpSocket);
            }
        }

        // 3. Test API Connectivity if configured
        if (! empty($server->api_url) && ! empty($server->api_key)) {
            try {
                $response = Http::timeout(2)
                    ->withHeaders(['X-API-Key' => $server->api_key])
                    ->get(rtrim($server->api_url, '/').'/api/v1/servers');

                $apiOnline = $response->successful();
            } catch (Throwable $e) {
                $apiOnline = false;
                $errorMessage = ($errorMessage ? $errorMessage.'; ' : '').'API error: '.$e->getMessage();
            }
        }

        $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

        $dnsPortOnline = $tcpOnline || $udpOnline;

        $status = 'offline';
        if ($dnsPortOnline && ($apiOnline === null || $apiOnline === true)) {
            $status = 'online';
        } elseif ($dnsPortOnline || $apiOnline === true) {
            $status = 'warning';
        }

        $server->update([
            'status' => $status,
            'last_check_at' => now(),
        ]);

        // Record Health Check History
        ServerHealthCheck::create([
            'dns_server_id' => $server->id,
            'status' => $status,
            'latency_ms' => $latencyMs,
            'port_53_tcp' => $tcpOnline,
            'port_53_udp' => $udpOnline,
            'api_status' => $apiOnline,
            'dns_query_status' => $dnsQueryOnline,
            'response_summary' => [
                'tcp' => $tcpOnline,
                'udp' => $udpOnline,
                'api' => $apiOnline,
                'latency_ms' => $latencyMs,
            ],
            'error_message' => $status === 'offline' ? ($errorMessage ?? 'DNS Server unreachable') : null,
        ]);

        return [
            'status' => $status,
            'latency_ms' => $latencyMs,
            'details' => [
                'port_53_tcp' => $tcpOnline,
                'port_53_udp' => $udpOnline,
                'dns_query' => $dnsQueryOnline,
                'api_connected' => $apiOnline,
            ],
        ];
    }

    /**
     * Apply a DNS Template to a DNS Zone, substituting variables like {ip}, {domain}.
     *
     * @param  array{ip?: string|null, server?: string|null}  $variables
     * @return array<PdnsRecord>
     *
     * @throws PowerDNSException
     */
    public function applyTemplate(PdnsDomain|int $domain, DnsTemplate|int $template, array $variables = []): array
    {
        $domainModel = $this->resolveDomain($domain);
        $templateModel = $template instanceof DnsTemplate ? $template : DnsTemplate::with('records')->findOrFail($template);

        $createdRecords = [];
        $ip = $variables['ip'] ?? '127.0.0.1';
        $server = $variables['server'] ?? 'mail.'.$domainModel->name;

        foreach ($templateModel->records as $tplRecord) {
            $name = str_replace(
                ['{domain}', '{ip}', '{server}'],
                [$domainModel->name, $ip, $server],
                $tplRecord->name
            );

            $value = str_replace(
                ['{domain}', '{ip}', '{server}'],
                [$domainModel->name, $ip, $server],
                $tplRecord->value
            );

            // Create record
            $created = $this->createRecord($domainModel, [
                'name' => $name,
                'type' => $tplRecord->type,
                'content' => $value,
                'ttl' => $tplRecord->ttl ?: 3600,
                'prio' => $tplRecord->priority,
            ]);

            $createdRecords[] = $created;
        }

        return $createdRecords;
    }

    /**
     * Perform DNS Lookup for a domain.
     *
     * @return array{server: string, latency_ms: float, count: int, records: array<int, array<string, mixed>>}
     */
    public function lookupDns(string $domain, string $type = 'A', ?string $nameserver = null): array
    {
        $domain = $this->normalizeZoneName($domain);
        $type = strtoupper($type);
        $startTime = microtime(true);
        $records = [];
        $serverUsed = $nameserver ?: 'System Default Resolver';

        if (! empty($nameserver)) {
            // Query via DoH or custom resolver
            try {
                $response = Http::timeout(3)
                    ->withHeaders(['Accept' => 'application/dns-json'])
                    ->get("https://dns.google/resolve?name={$domain}&type={$type}");

                if ($response->successful()) {
                    $json = $response->json();
                    $answers = $json['Answer'] ?? [];
                    foreach ($answers as $ans) {
                        $records[] = [
                            'host' => $ans['name'] ?? $domain,
                            'type' => $type,
                            'content' => $ans['data'] ?? '',
                            'ttl' => $ans['TTL'] ?? 300,
                        ];
                    }
                }
            } catch (Throwable) {
                // fallback to local resolver
            }
        }

        if (empty($records)) {
            $typeMap = [
                'A' => DNS_A,
                'AAAA' => DNS_AAAA,
                'CNAME' => DNS_CNAME,
                'MX' => DNS_MX,
                'TXT' => DNS_TXT,
                'NS' => DNS_NS,
                'SOA' => DNS_SOA,
                'CAA' => defined('DNS_CAA') ? DNS_CAA : DNS_ANY,
                'PTR' => DNS_PTR,
                'ANY' => DNS_ANY,
            ];

            $phpType = $typeMap[$type] ?? DNS_ANY;
            $raw = @dns_get_record($domain, $phpType);

            if (is_array($raw)) {
                foreach ($raw as $entry) {
                    $records[] = [
                        'host' => $entry['host'] ?? $domain,
                        'type' => $entry['type'] ?? $type,
                        'content' => $entry['target'] ?? ($entry['ip'] ?? ($entry['ipv6'] ?? ($entry['txt'] ?? ($entry['mname'] ?? json_encode($entry))))),
                        'ttl' => $entry['ttl'] ?? 3600,
                        'prio' => $entry['pri'] ?? null,
                    ];
                }
            }
        }

        // Also check if domain is locally hosted in our PowerDNS database
        if (empty($records)) {
            $localDomain = PdnsDomain::where('name', $domain)->first();
            if ($localDomain) {
                $query = $localDomain->records()->where('disabled', false);
                if ($type !== 'ANY') {
                    $query->where('type', $type);
                }
                foreach ($query->get() as $rec) {
                    $records[] = [
                        'host' => $rec->name,
                        'type' => $rec->type,
                        'content' => $rec->content,
                        'ttl' => $rec->ttl,
                        'prio' => $rec->prio,
                        'source' => 'Local Authoritative Backend',
                    ];
                }
            }
        }

        $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'server' => $serverUsed,
            'latency_ms' => $latencyMs,
            'count' => count($records),
            'records' => $records,
        ];
    }

    /**
     * Check Global DNS Propagation across multiple public nameservers.
     *
     * @return array<int, array{provider: string, ip: string, location: string, status: string, latency_ms: float, result: string|null}>
     */
    public function checkPropagation(string $domain, string $type = 'A'): array
    {
        $domain = $this->normalizeZoneName($domain);
        $type = strtoupper($type);

        $resolvers = [
            [
                'provider' => 'Google Public DNS',
                'ip' => '8.8.8.8',
                'location' => 'Global Anycast (USA)',
                'doh' => "https://dns.google/resolve?name={$domain}&type={$type}",
            ],
            [
                'provider' => 'Cloudflare DNS',
                'ip' => '1.1.1.1',
                'location' => 'Global Anycast (Cloudflare)',
                'doh' => "https://cloudflare-dns.com/dns-query?name={$domain}&type={$type}",
            ],
            [
                'provider' => 'Quad9 Security DNS',
                'ip' => '9.9.9.9',
                'location' => 'Global Anycast (Switzerland)',
                'doh' => "https://dns.quad9.net:5053/dns-query?name={$domain}&type={$type}",
            ],
            [
                'provider' => 'OpenDNS / Cisco',
                'ip' => '208.67.222.222',
                'location' => 'Global Anycast (Cisco)',
                'doh' => "https://doh.opendns.com/dns-query?name={$domain}&type={$type}",
            ],
            [
                'provider' => 'Control D',
                'ip' => '76.76.2.0',
                'location' => 'Global Anycast (Canada)',
                'doh' => "https://freedns.controld.com/p0?name={$domain}&type={$type}",
            ],
        ];

        $results = [];

        foreach ($resolvers as $resolver) {
            $startTime = microtime(true);
            $status = 'offline';
            $answerStr = null;

            try {
                $response = Http::timeout(2)
                    ->withHeaders(['Accept' => 'application/dns-json'])
                    ->get($resolver['doh']);

                $latency = round((microtime(true) - $startTime) * 1000, 2);

                if ($response->successful()) {
                    $json = $response->json();
                    $answers = $json['Answer'] ?? [];
                    if (! empty($answers)) {
                        $values = array_map(fn ($a) => $a['data'] ?? '', $answers);
                        $answerStr = implode(', ', $values);
                        $status = 'resolved';
                    } else {
                        $status = 'nodata';
                        $answerStr = 'NXDOMAIN / No Record';
                    }
                }
            } catch (Throwable) {
                $latency = round((microtime(true) - $startTime) * 1000, 2);
                $status = 'timeout';
                $answerStr = 'Timeout';
            }

            $results[] = [
                'provider' => $resolver['provider'],
                'ip' => $resolver['ip'],
                'location' => $resolver['location'],
                'status' => $status,
                'latency_ms' => $latency,
                'result' => $answerStr,
            ];
        }

        return $results;
    }

    /**
     * Export Zone to BIND Zone File format.
     *
     * @throws PowerDNSException
     */
    public function exportBindFormat(PdnsDomain|int $domain): string
    {
        $domainModel = $this->resolveDomain($domain);
        $records = $domainModel->records()->orderBy('type')->get();

        $output = [];
        $output[] = "; Zone: {$domainModel->name}";
        $output[] = '; Exported: '.now()->toIso8601String();
        $output[] = '$ORIGIN '.$this->normalizeFqdn($domainModel->name);
        $output[] = '$TTL '.config('powerdns.defaults.ttl', 3600);
        $output[] = '';

        // 1. SOA Record
        $soa = $records->firstWhere('type', 'SOA');
        if ($soa) {
            $output[] = "; SOA Record\n@\tIN\tSOA\t{$soa->content}";
            $output[] = '';
        }

        // 2. Other Records
        $output[] = '; DNS Records';
        foreach ($records as $rec) {
            if ($rec->type === 'SOA') {
                continue;
            }

            $prefix = $rec->name === $domainModel->name ? '@' : str_replace('.'.$domainModel->name, '', $rec->name);
            $prio = $rec->prio !== null ? "{$rec->prio}\t" : '';
            $disabled = $rec->disabled ? '; DISABLED: ' : '';

            $output[] = "{$disabled}{$prefix}\t{$rec->ttl}\tIN\t{$rec->type}\t{$prio}{$rec->content}";
        }

        return implode("\n", $output)."\n";
    }

    /**
     * Export Zone to JSON format.
     *
     * @throws PowerDNSException
     */
    public function exportJsonFormat(PdnsDomain|int $domain): array
    {
        $domainModel = $this->resolveDomain($domain);

        return [
            'name' => $domainModel->name,
            'type' => $domainModel->type,
            'exported_at' => now()->toIso8601String(),
            'records' => $domainModel->records->map(fn (PdnsRecord $r) => [
                'name' => $r->name,
                'type' => $r->type,
                'content' => $r->content,
                'ttl' => $r->ttl,
                'prio' => $r->prio,
                'disabled' => $r->disabled,
            ])->toArray(),
        ];
    }

    /**
     * Generate an initial SOA Serial in format YYYYMMDD01.
     */
    public function generateInitialSerial(): int
    {
        return (int) (date('Ymd').'01');
    }

    /**
     * Generate SOA record content string.
     */
    public function generateSoaContent(string $zoneName, int $serial): string
    {
        $primaryNs = $this->normalizeFqdn(config('powerdns.defaults.soa.primary_ns', 'ns1.example.com'));
        $hostmaster = $this->normalizeFqdn(config('powerdns.defaults.soa.hostmaster', 'hostmaster.example.com'));
        $refresh = (int) config('powerdns.defaults.soa.refresh', 10800);
        $retry = (int) config('powerdns.defaults.soa.retry', 3600);
        $expire = (int) config('powerdns.defaults.soa.expire', 604800);
        $minimum = (int) config('powerdns.defaults.soa.minimum', 3600);

        return "{$primaryNs} {$hostmaster} {$serial} {$refresh} {$retry} {$expire} {$minimum}";
    }

    /**
     * Normalize a zone name (lowercase, trim, strip trailing dot).
     */
    public function normalizeZoneName(string $name): string
    {
        return strtolower(rtrim(trim($name), '.'));
    }

    /**
     * Ensure a domain name ends with a trailing dot for standard BIND/PDNS content (e.g. for NS / CNAME / MX target).
     */
    public function normalizeFqdn(string $name): string
    {
        $trimmed = trim($name);

        return str_ends_with($trimmed, '.') ? $trimmed : $trimmed.'.';
    }

    /**
     * Qualify record name with zone name.
     * E.g. "www" in "example.com" -> "www.example.com"
     * "@" in "example.com" -> "example.com"
     */
    public function qualifyRecordName(string $name, string $zoneName): string
    {
        $name = strtolower(rtrim(trim($name), '.'));
        $zoneName = $this->normalizeZoneName($zoneName);

        if ($name === '' || $name === '@' || $name === $zoneName) {
            return $zoneName;
        }

        if (str_ends_with($name, '.'.$zoneName)) {
            return $name;
        }

        return "{$name}.{$zoneName}";
    }

    /**
     * Resolve domain model from instance or ID.
     *
     * @throws PowerDNSException
     */
    protected function resolveDomain(PdnsDomain|int $domain): PdnsDomain
    {
        if ($domain instanceof PdnsDomain) {
            return $domain;
        }

        $model = PdnsDomain::find($domain);
        if (! $model) {
            throw PowerDNSException::zoneNotFound($domain);
        }

        return $model;
    }

    /**
     * Resolve record model from instance or ID.
     *
     * @throws PowerDNSException
     */
    protected function resolveRecord(PdnsRecord|int $record): PdnsRecord
    {
        if ($record instanceof PdnsRecord) {
            return $record;
        }

        $model = PdnsRecord::with('domain')->find($record);
        if (! $model) {
            throw PowerDNSException::recordNotFound($record);
        }

        return $model;
    }
}

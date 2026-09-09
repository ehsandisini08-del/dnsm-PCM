<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\DnsServer;
use App\Models\DnsTemplate;
use App\Models\DnsTemplateRecord;
use App\Models\PdnsDomain;
use App\Models\PdnsRecord;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class BackupService
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        if (! File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }
    }

    /**
     * Get list of all backup files with metadata.
     *
     * @return array<int, array{filename: string, path: string, size_bytes: int, size_formatted: string, created_at: string, total_zones: int, total_records: int}>
     */
    public function listBackups(): array
    {
        $files = File::glob($this->backupDir.'/*.json');
        $backups = [];

        foreach ($files as $file) {
            $filename = basename($file);
            $size = File::size($file);
            $content = json_decode(File::get($file), true);

            $backups[] = [
                'filename' => $filename,
                'path' => $file,
                'size_bytes' => $size,
                'size_formatted' => $this->formatBytes($size),
                'created_at' => $content['metadata']['created_at'] ?? date('Y-m-d H:i:s', File::lastModified($file)),
                'total_zones' => $content['metadata']['total_zones'] ?? 0,
                'total_records' => $content['metadata']['total_records'] ?? 0,
            ];
        }

        // Sort latest first
        usort($backups, fn ($a, $b) => strcmp($b['created_at'], $a['created_at']));

        return $backups;
    }

    /**
     * Create a new full backup of application and PowerDNS data.
     *
     * @return array{filename: string, path: string, total_zones: int, total_records: int}
     *
     * @throws Exception
     */
    public function createBackup(?string $customName = null): array
    {
        $timestamp = date('Y-m-d_His');
        $prefix = $customName ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $customName).'_' : 'dns_backup_';
        $filename = "{$prefix}{$timestamp}.json";
        $filepath = $this->backupDir.'/'.$filename;

        $zones = PdnsDomain::with(['records', 'comments', 'metadata', 'cryptoKeys'])->get();
        $totalRecords = PdnsRecord::count();

        $data = [
            'metadata' => [
                'backup_name' => $customName ?? 'Automatic Backup',
                'created_at' => now()->toIso8601String(),
                'version' => '1.0.0',
                'app_name' => config('app.name'),
                'total_zones' => $zones->count(),
                'total_records' => $totalRecords,
            ],
            'database' => [
                'users' => User::all()->toArray(),
                'customers' => Customer::all()->toArray(),
                'dns_servers' => DnsServer::all()->toArray(),
                'dns_templates' => DnsTemplate::with('records')->get()->toArray(),
                'domains' => $zones->toArray(),
            ],
        ];

        File::put($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Audit the backup action
        app(AuditLogService::class)->log('CREATE_BACKUP', null, null, [
            'filename' => $filename,
            'total_zones' => $zones->count(),
            'total_records' => $totalRecords,
        ]);

        return [
            'filename' => $filename,
            'path' => $filepath,
            'total_zones' => $zones->count(),
            'total_records' => $totalRecords,
        ];
    }

    /**
     * Delete a backup file.
     */
    public function deleteBackup(string $filename): bool
    {
        $filepath = $this->backupDir.'/'.basename($filename);

        if (File::exists($filepath)) {
            $deleted = File::delete($filepath);

            app(AuditLogService::class)->log('DELETE_BACKUP', null, ['filename' => $filename], null);

            return $deleted;
        }

        return false;
    }

    /**
     * Restore database from a backup JSON file.
     *
     * @throws Exception
     */
    public function restoreBackup(string $filename): bool
    {
        $filepath = $this->backupDir.'/'.basename($filename);

        if (! File::exists($filepath)) {
            throw new Exception("Backup file [{$filename}] not found.");
        }

        $raw = File::get($filepath);
        $data = json_decode($raw, true);

        if (! is_array($data) || ! isset($data['database'])) {
            throw new Exception('Invalid backup format: missing database payload.');
        }

        try {
            DB::transaction(function () use ($data) {
                $db = $data['database'];

                // 1. Restore Customers
                if (isset($db['customers'])) {
                    foreach ($db['customers'] as $cust) {
                        Customer::updateOrCreate(['id' => $cust['id']], $cust);
                    }
                }

                // 2. Restore DNS Servers
                if (isset($db['dns_servers'])) {
                    foreach ($db['dns_servers'] as $srv) {
                        DnsServer::updateOrCreate(['id' => $srv['id']], $srv);
                    }
                }

                // 3. Restore DNS Templates
                if (isset($db['dns_templates'])) {
                    foreach ($db['dns_templates'] as $tpl) {
                        $templateModel = DnsTemplate::updateOrCreate(
                            ['id' => $tpl['id']],
                            [
                                'name' => $tpl['name'],
                                'description' => $tpl['description'] ?? null,
                                'is_active' => $tpl['is_active'] ?? true,
                            ]
                        );

                        if (! empty($tpl['records'])) {
                            $templateModel->records()->delete();
                            foreach ($tpl['records'] as $rec) {
                                DnsTemplateRecord::create([
                                    'dns_template_id' => $templateModel->id,
                                    'name' => $rec['name'],
                                    'type' => $rec['type'],
                                    'value' => $rec['value'],
                                    'ttl' => $rec['ttl'] ?? 3600,
                                    'priority' => $rec['priority'] ?? null,
                                ]);
                            }
                        }
                    }
                }

                // 4. Restore Domains & Records
                if (isset($db['domains'])) {
                    foreach ($db['domains'] as $dom) {
                        $domainModel = PdnsDomain::updateOrCreate(
                            ['id' => $dom['id']],
                            [
                                'name' => $dom['name'],
                                'type' => $dom['type'] ?? 'NATIVE',
                                'master' => $dom['master'] ?? null,
                                'customer_id' => $dom['customer_id'] ?? null,
                                'dns_server_id' => $dom['dns_server_id'] ?? null,
                                'status' => $dom['status'] ?? 'active',
                                'sync_status' => 'synced',
                            ]
                        );

                        if (! empty($dom['records'])) {
                            $domainModel->records()->delete();
                            foreach ($dom['records'] as $rec) {
                                PdnsRecord::create([
                                    'domain_id' => $domainModel->id,
                                    'name' => $rec['name'],
                                    'type' => $rec['type'],
                                    'content' => $rec['content'],
                                    'ttl' => $rec['ttl'] ?? 3600,
                                    'prio' => $rec['prio'] ?? null,
                                    'disabled' => $rec['disabled'] ?? false,
                                    'auth' => true,
                                ]);
                            }
                        }
                    }
                }
            });

            app(AuditLogService::class)->log('RESTORE_BACKUP', null, null, ['filename' => $filename]);

            return true;
        } catch (Throwable $e) {
            throw new Exception('Backup restoration failed: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Format bytes into readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}

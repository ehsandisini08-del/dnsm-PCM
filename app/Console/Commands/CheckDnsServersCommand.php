<?php

namespace App\Console\Commands;

use App\Jobs\CheckDnsServerJob;
use App\Models\DnsServer;
use App\Services\PowerDNSService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('dns:check-servers {--sync : Run health check synchronously instead of queuing}')]
#[Description('Perform health check on all configured DNS servers')]
class CheckDnsServersCommand extends Command
{
    public function handle(PowerDNSService $service): int
    {
        $servers = DnsServer::all();

        if ($servers->isEmpty()) {
            $this->info('No DNS servers configured to check.');

            return self::SUCCESS;
        }

        $this->info("Checking health for {$servers->count()} DNS server(s)...");

        $isSync = (bool) $this->option('sync');

        foreach ($servers as $server) {
            if ($isSync) {
                $result = $service->checkServerHealth($server);
                $this->line(" - [{$server->name} / {$server->ip_address}]: <info>{$result['status']}</info> ({$result['latency_ms']}ms)");
            } else {
                CheckDnsServerJob::dispatch($server);
                $this->line(" - Queued health check job for: {$server->name}");
            }
        }

        $this->info('DNS Server health check dispatched successfully.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Jobs;

use App\Models\DnsServer;
use App\Services\PowerDNSService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckDnsServerJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public DnsServer $server) {}

    public function handle(PowerDNSService $service): void
    {
        $service->checkServerHealth($this->server);
    }
}

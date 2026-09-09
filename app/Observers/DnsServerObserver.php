<?php

namespace App\Observers;

use App\Models\DnsServer;
use App\Services\AuditLogService;

class DnsServerObserver
{
    public function __construct(protected AuditLogService $auditService) {}

    public function created(DnsServer $server): void
    {
        $this->auditService->log('CREATE_SERVER', $server, null, $server->toArray());
    }

    public function updated(DnsServer $server): void
    {
        $changes = $server->getChanges();
        $original = array_intersect_key($server->getOriginal(), $changes);

        $this->auditService->log('UPDATE_SERVER', $server, $original, $changes);
    }

    public function deleted(DnsServer $server): void
    {
        $this->auditService->log('DELETE_SERVER', $server, $server->toArray(), null);
    }
}

<?php

namespace App\Observers;

use App\Models\PdnsDomain;
use App\Services\AuditLogService;

class PdnsDomainObserver
{
    public function __construct(protected AuditLogService $auditService) {}

    public function created(PdnsDomain $domain): void
    {
        $this->auditService->log('CREATE_ZONE', $domain, null, $domain->toArray());
    }

    public function updated(PdnsDomain $domain): void
    {
        $changes = $domain->getChanges();
        $original = array_intersect_key($domain->getOriginal(), $changes);

        $this->auditService->log('UPDATE_ZONE', $domain, $original, $changes);
    }

    public function deleted(PdnsDomain $domain): void
    {
        $this->auditService->log('DELETE_ZONE', $domain, $domain->toArray(), null);
    }
}

<?php

namespace App\Observers;

use App\Models\PdnsRecord;
use App\Services\AuditLogService;

class PdnsRecordObserver
{
    public function __construct(protected AuditLogService $auditService) {}

    public function created(PdnsRecord $record): void
    {
        $this->auditService->log('CREATE_RECORD', $record, null, $record->toArray());
    }

    public function updated(PdnsRecord $record): void
    {
        $changes = $record->getChanges();
        $original = array_intersect_key($record->getOriginal(), $changes);

        $this->auditService->log('UPDATE_RECORD', $record, $original, $changes);
    }

    public function deleted(PdnsRecord $record): void
    {
        $this->auditService->log('DELETE_RECORD', $record, $record->toArray(), null);
    }
}

<?php

namespace App\Observers;

use App\Models\Customer;
use App\Services\AuditLogService;

class CustomerObserver
{
    public function __construct(protected AuditLogService $auditService) {}

    public function created(Customer $customer): void
    {
        $this->auditService->log('CREATE_CUSTOMER', $customer, null, $customer->toArray());
    }

    public function updated(Customer $customer): void
    {
        $changes = $customer->getChanges();
        $original = array_intersect_key($customer->getOriginal(), $changes);

        $this->auditService->log('UPDATE_CUSTOMER', $customer, $original, $changes);
    }

    public function deleted(Customer $customer): void
    {
        $this->auditService->log('DELETE_CUSTOMER', $customer, $customer->toArray(), null);
    }
}

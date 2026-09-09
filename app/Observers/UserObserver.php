<?php

namespace App\Observers;

use App\Models\User;
use App\Services\AuditLogService;

class UserObserver
{
    public function __construct(protected AuditLogService $auditService) {}

    public function created(User $user): void
    {
        $this->auditService->log('CREATE_USER', $user, null, $user->getAttributes());
    }

    public function updated(User $user): void
    {
        $changes = $user->getChanges();
        $original = array_intersect_key($user->getOriginal(), $changes);

        $this->auditService->log('UPDATE_USER', $user, $original, $changes);
    }

    public function deleted(User $user): void
    {
        $this->auditService->log('DELETE_USER', $user, $user->getAttributes(), null);
    }
}

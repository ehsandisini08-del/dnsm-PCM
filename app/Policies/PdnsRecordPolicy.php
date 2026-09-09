<?php

namespace App\Policies;

use App\Models\PdnsRecord;
use App\Models\User;

class PdnsRecordPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->is_active) {
            return false;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'dns_admin', 'operator'], true);
    }

    public function view(User $user, PdnsRecord $record): bool
    {
        return in_array($user->role, ['super_admin', 'dns_admin', 'operator'], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'dns_admin', 'operator'], true);
    }

    public function update(User $user, PdnsRecord $record): bool
    {
        return in_array($user->role, ['super_admin', 'dns_admin', 'operator'], true);
    }

    public function delete(User $user, PdnsRecord $record): bool
    {
        // SOA records can never be deleted
        if ($record->type === 'SOA') {
            return false;
        }

        return in_array($user->role, ['super_admin', 'dns_admin', 'operator'], true);
    }
}

<?php

namespace App\Policies;

use App\Models\DnsTemplate;
use App\Models\User;

class DnsTemplatePolicy
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

    public function view(User $user, DnsTemplate $dnsTemplate): bool
    {
        return in_array($user->role, ['super_admin', 'dns_admin', 'operator'], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'dns_admin'], true);
    }

    public function update(User $user, DnsTemplate $dnsTemplate): bool
    {
        return in_array($user->role, ['super_admin', 'dns_admin'], true);
    }

    public function delete(User $user, DnsTemplate $dnsTemplate): bool
    {
        return in_array($user->role, ['super_admin', 'dns_admin'], true);
    }
}

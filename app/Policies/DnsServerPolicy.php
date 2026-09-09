<?php

namespace App\Policies;

use App\Models\DnsServer;
use App\Models\User;

class DnsServerPolicy
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

    public function view(User $user, DnsServer $dnsServer): bool
    {
        return in_array($user->role, ['super_admin', 'dns_admin', 'operator'], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'dns_admin'], true);
    }

    public function update(User $user, DnsServer $dnsServer): bool
    {
        return in_array($user->role, ['super_admin', 'dns_admin'], true);
    }

    public function delete(User $user, DnsServer $dnsServer): bool
    {
        return $user->isSuperAdmin();
    }
}

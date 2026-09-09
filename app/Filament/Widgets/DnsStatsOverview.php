<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\DnsServer;
use App\Models\PdnsDomain;
use App\Models\PdnsRecord;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DnsStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalZones = PdnsDomain::count();
        $totalRecords = PdnsRecord::count();
        $totalCustomers = Customer::count();
        $totalServers = DnsServer::count();
        $onlineServers = DnsServer::where('status', 'online')->count();
        $offlineServers = DnsServer::where('status', 'offline')->count();

        return [
            Stat::make('Managed Zones', number_format($totalZones))
                ->description('Authoritative DNS Zones')
                ->descriptionIcon('heroicon-m-globe-americas')
                ->chart([3, 5, 8, 12, 15, 18, $totalZones ?: 1])
                ->color('primary'),

            Stat::make('DNS Records', number_format($totalRecords))
                ->description('Active Resource Records')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->chart([10, 25, 45, 60, 80, 110, $totalRecords ?: 1])
                ->color('info'),

            Stat::make('ISP Customers', number_format($totalCustomers))
                ->description('Active Client Accounts')
                ->descriptionIcon('heroicon-m-user-group')
                ->chart([1, 2, 4, 6, 8, 10, $totalCustomers ?: 1])
                ->color('success'),

            Stat::make('Cluster Health', "{$onlineServers} / {$totalServers} Live")
                ->description($offlineServers > 0 ? "{$offlineServers} server(s) degraded" : 'All nameservers operational')
                ->descriptionIcon('heroicon-m-server-stack')
                ->chart([$totalServers ?: 1, $totalServers ?: 1, $onlineServers ?: 1])
                ->color($offlineServers > 0 ? 'danger' : 'success'),
        ];
    }
}

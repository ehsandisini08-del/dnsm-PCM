<?php

namespace App\Filament\Widgets;

use App\Models\DnsServer;
use App\Services\PowerDNSService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class ServerStatusGrid extends Widget
{
    protected string $view = 'filament.widgets.server-status-grid';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 2,
    ];

    public function getServers()
    {
        return DnsServer::withCount('zones')->orderBy('name')->get();
    }

    public function checkServer(int $serverId): void
    {
        $server = DnsServer::find($serverId);
        if ($server) {
            $result = app(PowerDNSService::class)->checkServerHealth($server);
            Notification::make()
                ->title("Checked {$server->name}")
                ->body("Status: {$result['status']} ({$result['latency_ms']} ms)")
                ->color($result['status'] === 'online' ? 'success' : 'danger')
                ->send();
        }
    }
}

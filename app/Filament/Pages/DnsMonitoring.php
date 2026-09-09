<?php

namespace App\Filament\Pages;

use App\Models\DnsServer;
use App\Models\ServerHealthCheck;
use App\Services\PowerDNSService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DnsMonitoring extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.dns-monitoring';

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoring';

    protected static ?string $navigationLabel = 'Server Health & Metrics';

    protected static ?string $title = 'DNS Nameserver Cluster Health Monitoring';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    public static function getNavigationBadge(): ?string
    {
        $offlineCount = DnsServer::where('status', 'offline')->count();

        return $offlineCount > 0 ? "{$offlineCount} Offline" : 'Healthy';
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return DnsServer::where('status', 'offline')->exists() ? 'danger' : 'success';
    }

    public function getServersProperty()
    {
        return DnsServer::withCount('zones')->orderBy('name')->get();
    }

    public function runAllHealthChecks(): void
    {
        $service = app(PowerDNSService::class);
        $servers = DnsServer::all();

        foreach ($servers as $server) {
            $service->checkServerHealth($server);
        }

        Notification::make()
            ->title('Diagnostic Completed')
            ->body('Checked health status for all '.$servers->count().' nameservers.')
            ->success()
            ->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ServerHealthCheck::query()->with('server')->latest())
            ->columns([
                TextColumn::make('created_at')
                    ->label('Checked At')
                    ->since()
                    ->sortable(),

                TextColumn::make('server.name')
                    ->label('DNS Server')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('server.ip_address')
                    ->label('IP Address')
                    ->fontFamily('mono')
                    ->copyable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'online' => 'success',
                        'warning' => 'warning',
                        'offline' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('latency_ms')
                    ->label('Latency')
                    ->formatStateUsing(fn ($state) => $state !== null ? "{$state} ms" : '—')
                    ->fontFamily('mono')
                    ->sortable(),

                IconColumn::make('port_53_tcp')
                    ->label('TCP 53')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                IconColumn::make('port_53_udp')
                    ->label('UDP 53')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                IconColumn::make('api_status')
                    ->label('API')
                    ->placeholder('N/A')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                TextColumn::make('error_message')
                    ->label('Diagnostic Output / Error')
                    ->limit(40)
                    ->placeholder('None (OK)')
                    ->tooltip(fn (ServerHealthCheck $record): ?string => $record->error_message),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'online' => 'Online',
                        'warning' => 'Warning',
                        'offline' => 'Offline',
                    ]),

                SelectFilter::make('dns_server_id')
                    ->label('Server')
                    ->relationship('server', 'name'),
            ])
            ->paginated([10, 25, 50]);
    }
}

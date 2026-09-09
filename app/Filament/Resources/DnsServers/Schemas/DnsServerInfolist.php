<?php

namespace App\Filament\Resources\DnsServers\Schemas;

use App\Models\DnsServer;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DnsServerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Server Name')
                    ->weight('bold'),

                TextEntry::make('hostname')
                    ->label('Hostname (FQDN)')
                    ->copyable(),

                TextEntry::make('ip_address')
                    ->label('IP Address')
                    ->copyable(),

                TextEntry::make('type')
                    ->badge()
                    ->color('info'),

                TextEntry::make('port')
                    ->label('DNS Port'),

                TextEntry::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'online' => 'success',
                        'offline' => 'danger',
                        'warning' => 'warning',
                        default => 'secondary',
                    }),

                TextEntry::make('zones_count')
                    ->label('Total Assigned Zones')
                    ->state(fn (DnsServer $record): int => $record->zones()->count())
                    ->badge()
                    ->color('primary'),

                TextEntry::make('last_check_at')
                    ->label('Last Health Check')
                    ->dateTime()
                    ->placeholder('Never checked'),

                TextEntry::make('api_url')
                    ->label('PowerDNS API URL')
                    ->placeholder('Not configured'),

                TextEntry::make('description')
                    ->label('Description')
                    ->placeholder('—')
                    ->columnSpanFull(),
            ]);
    }
}

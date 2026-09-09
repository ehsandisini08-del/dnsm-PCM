<?php

namespace App\Filament\Resources\PdnsDomains\Schemas;

use App\Models\PdnsDomain;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PdnsDomainInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Zone Name')
                    ->weight('bold')
                    ->copyable(),

                TextEntry::make('type')
                    ->label('Zone Type')
                    ->badge(),

                TextEntry::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'disabled' => 'gray',
                        'suspended' => 'danger',
                        default => 'secondary',
                    }),

                TextEntry::make('sync_status')
                    ->label('Sync Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'synced' => 'success',
                        'pending' => 'warning',
                        'error' => 'danger',
                        default => 'secondary',
                    }),

                TextEntry::make('customer.name')
                    ->label('Customer')
                    ->placeholder('—'),

                TextEntry::make('dnsServer.name')
                    ->label('DNS Server')
                    ->placeholder('Default / Cluster'),

                TextEntry::make('records_count')
                    ->label('Total Records')
                    ->state(fn (PdnsDomain $record): int => $record->records()->count())
                    ->badge()
                    ->color('info'),

                TextEntry::make('soaRecord.content')
                    ->label('SOA Record')
                    ->placeholder('Not generated'),

                TextEntry::make('created_at')
                    ->label('Created At')
                    ->dateTime(),

                TextEntry::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime(),
            ]);
    }
}

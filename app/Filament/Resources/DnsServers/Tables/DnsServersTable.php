<?php

namespace App\Filament\Resources\DnsServers\Tables;

use App\Models\DnsServer;
use App\Services\PowerDNSService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Throwable;

class DnsServersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Server Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('hostname')
                    ->label('Hostname')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('type')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('zones_count')
                    ->counts('zones')
                    ->label('Assigned Zones')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'online' => 'success',
                        'offline' => 'danger',
                        'warning' => 'warning',
                        default => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('last_check_at')
                    ->label('Last Check')
                    ->since()
                    ->sortable()
                    ->placeholder('Never checked'),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'warning' => 'Warning',
                    ]),

                SelectFilter::make('type')
                    ->options([
                        'authoritative' => 'Authoritative',
                        'recursive' => 'Recursive',
                        'both' => 'Both',
                    ]),
            ])
            ->recordActions([
                Action::make('check_health')
                    ->label('Check Health')
                    ->icon('heroicon-o-heart')
                    ->color('success')
                    ->action(function (DnsServer $record) {
                        try {
                            $res = app(PowerDNSService::class)->checkServerHealth($record);
                            Notification::make()
                                ->title('Health Check Completed')
                                ->body("Server is {$res['status']} (Latency: {$res['latency_ms']}ms)")
                                ->color($res['status'] === 'online' ? 'success' : ($res['status'] === 'warning' ? 'warning' : 'danger'))
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title('Health Check Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\PdnsRecords\Tables;

use App\Models\PdnsRecord;
use App\Services\PowerDNSService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PdnsRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Host / Name')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('medium'),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'A', 'AAAA' => 'primary',
                        'CNAME' => 'info',
                        'MX' => 'warning',
                        'TXT' => 'gray',
                        'NS' => 'success',
                        'SOA' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('domain.name')
                    ->label('Zone')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('content')
                    ->label('Content / Value')
                    ->searchable()
                    ->limit(50)
                    ->copyable(),

                TextColumn::make('ttl')
                    ->label('TTL')
                    ->sortable(),

                TextColumn::make('prio')
                    ->label('Priority')
                    ->placeholder('—')
                    ->sortable(),

                IconColumn::make('disabled')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->state(fn (PdnsRecord $record): bool => ! $record->disabled)
                    ->sortable(),
            ])
            ->defaultSort('domain_id', 'asc')
            ->filters([
                SelectFilter::make('type')
                    ->options(array_combine(PowerDNSService::SUPPORTED_TYPES, PowerDNSService::SUPPORTED_TYPES)),

                SelectFilter::make('domain_id')
                    ->label('Zone')
                    ->relationship('domain', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('disabled')
                    ->label('Status')
                    ->trueLabel('Disabled')
                    ->falseLabel('Active Only')
                    ->queries(
                        true: fn ($query) => $query->where('disabled', true),
                        false: fn ($query) => $query->where('disabled', false),
                    ),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('toggle_active')
                    ->label(fn (PdnsRecord $record): string => $record->disabled ? 'Enable' : 'Disable')
                    ->icon('heroicon-o-power')
                    ->color(fn (PdnsRecord $record): string => $record->disabled ? 'success' : 'warning')
                    ->action(function (PdnsRecord $record) {
                        $newDisabled = ! $record->disabled;
                        app(PowerDNSService::class)->updateRecord($record, ['disabled' => $newDisabled]);

                        Notification::make()
                            ->title('Status Updated')
                            ->body('Record is now '.($newDisabled ? 'disabled' : 'enabled').'.')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()
                    ->hidden(fn (PdnsRecord $record): bool => $record->type === 'SOA')
                    ->using(function (PdnsRecord $record) {
                        return app(PowerDNSService::class)->deleteRecord($record);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $service = app(PowerDNSService::class);
                            foreach ($records as $record) {
                                if ($record->type !== 'SOA') {
                                    $service->deleteRecord($record);
                                }
                            }
                        }),
                ]),
            ]);
    }
}

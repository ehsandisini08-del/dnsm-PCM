<?php

namespace App\Filament\Resources\PdnsDomains\Tables;

use App\Models\DnsTemplate;
use App\Models\PdnsDomain;
use App\Services\BindZoneParser;
use App\Services\PowerDNSService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PdnsDomainsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Zone Name')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('dnsServer.name')
                    ->label('DNS Server')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Default'),

                TextColumn::make('records_count')
                    ->counts('records')
                    ->label('Records')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'disabled' => 'gray',
                        'suspended' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('sync_status')
                    ->label('Sync')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'synced' => 'success',
                        'pending' => 'warning',
                        'error' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'NATIVE' => 'NATIVE',
                        'MASTER' => 'MASTER',
                        'SLAVE' => 'SLAVE',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'disabled' => 'Disabled',
                        'suspended' => 'Suspended',
                    ]),

                SelectFilter::make('sync_status')
                    ->options([
                        'synced' => 'Synced',
                        'pending' => 'Pending',
                        'error' => 'Error',
                    ]),

                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name'),

                SelectFilter::make('dns_server_id')
                    ->label('DNS Server')
                    ->relationship('dnsServer', 'name'),
            ])
            ->headerActions([
                Action::make('import_zone')
                    ->label('Import Zone (BIND / JSON)')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->form([
                        Select::make('format')
                            ->label('Import Format')
                            ->options([
                                'bind' => 'BIND Zone File Format',
                                'json' => 'JSON Format',
                            ])
                            ->default('bind')
                            ->required()
                            ->live(),

                        TextInput::make('zone_name')
                            ->label('Zone Name (Optional if in BIND $ORIGIN or JSON)')
                            ->placeholder('e.g. example.com')
                            ->nullable(),

                        Select::make('customer_id')
                            ->label('Assign to Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Select::make('dns_server_id')
                            ->label('Assign to DNS Server')
                            ->relationship('dnsServer', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Textarea::make('content')
                            ->label('Zone File Raw Text / Content')
                            ->placeholder("; Example BIND Zone\n\$ORIGIN example.com.\n\$TTL 3600\n@ IN SOA ns1.example.com. hostmaster.example.com. 2026090801 10800 3600 604800 3600\n@ IN NS ns1.example.com.\n@ IN A 103.10.10.1\nwww IN CNAME example.com.")
                            ->rows(10)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        try {
                            if ($data['format'] === 'json') {
                                $jsonData = json_decode($data['content'], true);
                                if (! is_array($jsonData)) {
                                    throw new \Exception('Invalid JSON format provided.');
                                }
                                $domain = BindZoneParser::importJson(
                                    $jsonData,
                                    $data['customer_id'] ?? null,
                                    $data['dns_server_id'] ?? null
                                );
                            } else {
                                $parsed = BindZoneParser::parse($data['content']);
                                $domain = BindZoneParser::import(
                                    $parsed,
                                    $data['zone_name'] ?? null,
                                    $data['customer_id'] ?? null,
                                    $data['dns_server_id'] ?? null
                                );
                            }

                            Notification::make()
                                ->title('Zone Imported Successfully')
                                ->body("Zone [{$domain->name}] imported with {$domain->records()->count()} record(s).")
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title('Import Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('apply_template')
                    ->label('Apply Template')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('primary')
                    ->form([
                        Select::make('template_id')
                            ->label('Choose DNS Template')
                            ->options(DnsTemplate::where('is_active', true)->pluck('name', 'id'))
                            ->required(),
                        TextInput::make('ip')
                            ->label('Target IP Address ({ip})')
                            ->placeholder('103.10.10.1')
                            ->ip()
                            ->required(),
                        TextInput::make('server')
                            ->label('Mail/Target Server Hostname ({server})')
                            ->placeholder('mail.domain.com')
                            ->nullable(),
                    ])
                    ->action(function (PdnsDomain $record, array $data) {
                        try {
                            $template = DnsTemplate::with('records')->findOrFail($data['template_id']);
                            $created = app(PowerDNSService::class)->applyTemplate($record, $template, [
                                'ip' => $data['ip'],
                                'server' => $data['server'] ?? null,
                            ]);

                            Notification::make()
                                ->title('Template Applied')
                                ->body(count($created)." records generated for [{$record->name}] from template [{$template->name}].")
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title('Failed to Apply Template')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('sync')
                    ->label('Sync')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (PdnsDomain $record) {
                        try {
                            $success = app(PowerDNSService::class)->syncZone($record);
                            if ($success) {
                                Notification::make()
                                    ->title('Zone Synced')
                                    ->body("Zone [{$record->name}] synchronized successfully.")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Sync Failed')
                                    ->body($record->fresh()->sync_error ?? 'Sync error occurred.')
                                    ->danger()
                                    ->send();
                            }
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title('Sync Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('toggle_status')
                    ->label(fn (PdnsDomain $record): string => $record->status === 'active' ? 'Disable' : 'Enable')
                    ->icon('heroicon-o-power')
                    ->color(fn (PdnsDomain $record): string => $record->status === 'active' ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(function (PdnsDomain $record) {
                        $newStatus = $record->status === 'active' ? 'disabled' : 'active';
                        $record->update(['status' => $newStatus]);

                        Notification::make()
                            ->title('Status Updated')
                            ->body("Zone [{$record->name}] is now {$newStatus}.")
                            ->success()
                            ->send();
                    }),

                Action::make('export_bind')
                    ->label('Export BIND')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (PdnsDomain $record): StreamedResponse {
                        $content = app(PowerDNSService::class)->exportBindFormat($record);
                        $filename = "{$record->name}.zone";

                        return response()->streamDownload(function () use ($content) {
                            echo $content;
                        }, $filename, [
                            'Content-Type' => 'text/plain',
                        ]);
                    }),

                DeleteAction::make()
                    ->using(function (PdnsDomain $record) {
                        return app(PowerDNSService::class)->deleteZone($record);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

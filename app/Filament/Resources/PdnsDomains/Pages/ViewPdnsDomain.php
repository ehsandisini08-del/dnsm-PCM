<?php

namespace App\Filament\Resources\PdnsDomains\Pages;

use App\Filament\Resources\PdnsDomains\PdnsDomainResource;
use App\Models\DnsTemplate;
use App\Models\PdnsDomain;
use App\Services\PowerDNSService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ViewPdnsDomain extends ViewRecord
{
    protected static string $resource = PdnsDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
                ->action(function (array $data) {
                    /** @var PdnsDomain $record */
                    $record = $this->getRecord();
                    try {
                        $template = DnsTemplate::with('records')->findOrFail($data['template_id']);
                        $created = app(PowerDNSService::class)->applyTemplate($record, $template, [
                            'ip' => $data['ip'],
                            'server' => $data['server'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Template Applied')
                            ->body(count($created)." records generated from template [{$template->name}].")
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
                ->label('Sync Zone')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function () {
                    /** @var PdnsDomain $record */
                    $record = $this->getRecord();
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

            Action::make('export_bind')
                ->label('Export BIND')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function (): StreamedResponse {
                    /** @var PdnsDomain $record */
                    $record = $this->getRecord();
                    $content = app(PowerDNSService::class)->exportBindFormat($record);
                    $filename = "{$record->name}.zone";

                    return response()->streamDownload(function () use ($content) {
                        echo $content;
                    }, $filename, [
                        'Content-Type' => 'text/plain',
                    ]);
                }),
        ];
    }
}

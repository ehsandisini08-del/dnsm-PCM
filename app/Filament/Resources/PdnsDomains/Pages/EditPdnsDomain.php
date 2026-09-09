<?php

namespace App\Filament\Resources\PdnsDomains\Pages;

use App\Filament\Resources\PdnsDomains\PdnsDomainResource;
use App\Models\PdnsDomain;
use App\Services\PowerDNSService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class EditPdnsDomain extends EditRecord
{
    protected static string $resource = PdnsDomainResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(PowerDNSService::class)->updateZone($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),

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

            DeleteAction::make()
                ->using(function (PdnsDomain $record) {
                    return app(PowerDNSService::class)->deleteZone($record);
                }),
        ];
    }
}

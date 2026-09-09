<?php

namespace App\Filament\Resources\PdnsRecords\Pages;

use App\Filament\Resources\PdnsRecords\PdnsRecordResource;
use App\Models\PdnsRecord;
use App\Services\PowerDNSService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPdnsRecord extends EditRecord
{
    protected static string $resource = PdnsRecordResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(PowerDNSService::class)->updateRecord($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (): bool => $this->getRecord()->type === 'SOA')
                ->using(function (PdnsRecord $record) {
                    return app(PowerDNSService::class)->deleteRecord($record);
                }),
        ];
    }
}

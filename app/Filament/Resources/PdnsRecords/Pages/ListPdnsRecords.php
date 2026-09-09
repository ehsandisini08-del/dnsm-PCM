<?php

namespace App\Filament\Resources\PdnsRecords\Pages;

use App\Filament\Resources\PdnsRecords\PdnsRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPdnsRecords extends ListRecords
{
    protected static string $resource = PdnsRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

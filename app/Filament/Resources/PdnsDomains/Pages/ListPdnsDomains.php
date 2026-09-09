<?php

namespace App\Filament\Resources\PdnsDomains\Pages;

use App\Filament\Resources\PdnsDomains\PdnsDomainResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPdnsDomains extends ListRecords
{
    protected static string $resource = PdnsDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

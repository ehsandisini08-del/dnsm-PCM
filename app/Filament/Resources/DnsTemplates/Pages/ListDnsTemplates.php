<?php

namespace App\Filament\Resources\DnsTemplates\Pages;

use App\Filament\Resources\DnsTemplates\DnsTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDnsTemplates extends ListRecords
{
    protected static string $resource = DnsTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\DnsTemplates\Pages;

use App\Filament\Resources\DnsTemplates\DnsTemplateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDnsTemplate extends ViewRecord
{
    protected static string $resource = DnsTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

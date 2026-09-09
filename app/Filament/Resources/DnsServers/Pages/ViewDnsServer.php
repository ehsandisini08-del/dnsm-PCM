<?php

namespace App\Filament\Resources\DnsServers\Pages;

use App\Filament\Resources\DnsServers\DnsServerResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDnsServer extends ViewRecord
{
    protected static string $resource = DnsServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

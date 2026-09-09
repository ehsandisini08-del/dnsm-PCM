<?php

namespace App\Filament\Resources\DnsServers\Pages;

use App\Filament\Resources\DnsServers\DnsServerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDnsServers extends ListRecords
{
    protected static string $resource = DnsServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

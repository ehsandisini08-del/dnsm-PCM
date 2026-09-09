<?php

namespace App\Filament\Resources\DnsServers\Pages;

use App\Filament\Resources\DnsServers\DnsServerResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDnsServer extends EditRecord
{
    protected static string $resource = DnsServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

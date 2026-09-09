<?php

namespace App\Filament\Resources\DnsServers\Pages;

use App\Filament\Resources\DnsServers\DnsServerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDnsServer extends CreateRecord
{
    protected static string $resource = DnsServerResource::class;
}

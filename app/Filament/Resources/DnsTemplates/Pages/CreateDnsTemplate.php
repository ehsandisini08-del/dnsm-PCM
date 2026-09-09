<?php

namespace App\Filament\Resources\DnsTemplates\Pages;

use App\Filament\Resources\DnsTemplates\DnsTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDnsTemplate extends CreateRecord
{
    protected static string $resource = DnsTemplateResource::class;
}

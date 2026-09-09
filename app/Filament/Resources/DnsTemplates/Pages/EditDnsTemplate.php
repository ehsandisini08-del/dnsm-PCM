<?php

namespace App\Filament\Resources\DnsTemplates\Pages;

use App\Filament\Resources\DnsTemplates\DnsTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDnsTemplate extends EditRecord
{
    protected static string $resource = DnsTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

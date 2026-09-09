<?php

namespace App\Filament\Resources\PdnsDomains\Pages;

use App\Filament\Resources\PdnsDomains\PdnsDomainResource;
use App\Services\PowerDNSService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePdnsDomain extends CreateRecord
{
    protected static string $resource = PdnsDomainResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(PowerDNSService::class)->createZone($data);
    }
}

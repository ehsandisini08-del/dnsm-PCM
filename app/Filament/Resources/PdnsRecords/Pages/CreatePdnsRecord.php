<?php

namespace App\Filament\Resources\PdnsRecords\Pages;

use App\Filament\Resources\PdnsRecords\PdnsRecordResource;
use App\Models\PdnsDomain;
use App\Services\PowerDNSService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePdnsRecord extends CreateRecord
{
    protected static string $resource = PdnsRecordResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $domain = PdnsDomain::findOrFail($data['domain_id']);

        return app(PowerDNSService::class)->createRecord($domain, $data);
    }
}

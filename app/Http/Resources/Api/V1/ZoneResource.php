<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PdnsDomain;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PdnsDomain
 */
class ZoneResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'master' => $this->master,
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer?->id,
                'name' => $this->customer?->name,
                'email' => $this->customer?->email,
            ]),
            'dns_server_id' => $this->dns_server_id,
            'dns_server' => $this->whenLoaded('dnsServer', fn () => [
                'id' => $this->dnsServer?->id,
                'name' => $this->dnsServer?->name,
                'hostname' => $this->dnsServer?->hostname,
            ]),
            'status' => $this->status,
            'sync_status' => $this->sync_status,
            'sync_error' => $this->sync_error,
            'records_count' => $this->whenCounted('records'),
            'records' => RecordResource::collection($this->whenLoaded('records')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

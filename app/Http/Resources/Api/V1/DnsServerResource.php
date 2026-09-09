<?php

namespace App\Http\Resources\Api\V1;

use App\Models\DnsServer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DnsServer
 */
class DnsServerResource extends JsonResource
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
            'hostname' => $this->hostname,
            'ip_address' => $this->ip_address,
            'type' => $this->type,
            'status' => $this->status,
            'port' => $this->port,
            'api_url' => $this->api_url,
            'description' => $this->description,
            'last_check_at' => $this->last_check_at?->toIso8601String(),
            'zones_count' => $this->whenCounted('zones'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

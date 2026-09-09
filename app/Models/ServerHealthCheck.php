<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerHealthCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'dns_server_id',
        'status',
        'latency_ms',
        'port_53_tcp',
        'port_53_udp',
        'api_status',
        'dns_query_status',
        'response_summary',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'latency_ms' => 'float',
            'port_53_tcp' => 'boolean',
            'port_53_udp' => 'boolean',
            'api_status' => 'boolean',
            'dns_query_status' => 'boolean',
            'response_summary' => 'array',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(DnsServer::class, 'dns_server_id');
    }
}

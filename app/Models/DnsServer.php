<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DnsServer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'hostname',
        'ip_address',
        'type',
        'status',
        'port',
        'api_url',
        'api_key',
        'description',
        'last_check_at',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected function casts(): array
    {
        return [
            'last_check_at' => 'datetime',
            'port' => 'integer',
        ];
    }

    public function zones(): HasMany
    {
        return $this->hasMany(PdnsDomain::class, 'dns_server_id');
    }

    public function healthChecks(): HasMany
    {
        return $this->hasMany(ServerHealthCheck::class, 'dns_server_id')->latest();
    }
}

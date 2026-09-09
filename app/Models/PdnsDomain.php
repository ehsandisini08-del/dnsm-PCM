<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PdnsDomain extends Model
{
    use HasFactory;

    protected $table = 'domains';

    protected $fillable = [
        'name',
        'master',
        'last_check',
        'type',
        'notified_serial',
        'account',
        'options',
        'catalog',
        'customer_id',
        'dns_server_id',
        'status',
        'sync_status',
        'sync_error',
    ];

    protected function casts(): array
    {
        return [
            'last_check' => 'integer',
            'notified_serial' => 'integer',
            'status' => 'string',
            'sync_status' => 'string',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function dnsServer(): BelongsTo
    {
        return $this->belongsTo(DnsServer::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(PdnsRecord::class, 'domain_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PdnsComment::class, 'domain_id');
    }

    public function metadata(): HasMany
    {
        return $this->hasMany(PdnsMetadata::class, 'domain_id');
    }

    public function cryptoKeys(): HasMany
    {
        return $this->hasMany(PdnsCryptoKey::class, 'domain_id');
    }

    public function soaRecord(): HasOne
    {
        return $this->hasOne(PdnsRecord::class, 'domain_id')->where('type', 'SOA');
    }

    /**
     * Scope for active domains
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

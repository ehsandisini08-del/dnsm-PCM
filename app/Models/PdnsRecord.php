<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdnsRecord extends Model
{
    use HasFactory;

    protected $table = 'records';

    protected $fillable = [
        'domain_id',
        'name',
        'type',
        'content',
        'ttl',
        'prio',
        'disabled',
        'ordername',
        'auth',
    ];

    protected function casts(): array
    {
        return [
            'domain_id' => 'integer',
            'ttl' => 'integer',
            'prio' => 'integer',
            'disabled' => 'boolean',
            'auth' => 'boolean',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(PdnsDomain::class, 'domain_id');
    }

    public function scopeActive($query)
    {
        return $query->where('disabled', false);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', strtoupper($type));
    }
}

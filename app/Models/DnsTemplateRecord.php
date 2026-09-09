<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DnsTemplateRecord extends Model
{
    protected $fillable = [
        'dns_template_id',
        'name',
        'type',
        'value',
        'ttl',
        'priority',
        'weight',
        'port',
    ];

    protected function casts(): array
    {
        return [
            'ttl' => 'integer',
            'priority' => 'integer',
            'weight' => 'integer',
            'port' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DnsTemplate::class, 'dns_template_id');
    }
}

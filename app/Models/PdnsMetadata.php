<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdnsMetadata extends Model
{
    public $timestamps = false;

    protected $table = 'domainmetadata';

    protected $fillable = [
        'domain_id',
        'kind',
        'content',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(PdnsDomain::class, 'domain_id');
    }
}

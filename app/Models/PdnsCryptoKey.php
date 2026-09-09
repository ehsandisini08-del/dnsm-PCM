<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdnsCryptoKey extends Model
{
    public $timestamps = false;

    protected $table = 'cryptokeys';

    protected $fillable = [
        'domain_id',
        'flags',
        'active',
        'published',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'flags' => 'integer',
            'active' => 'boolean',
            'published' => 'boolean',
        ];
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(PdnsDomain::class, 'domain_id');
    }
}

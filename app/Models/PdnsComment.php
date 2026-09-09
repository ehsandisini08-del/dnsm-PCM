<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdnsComment extends Model
{
    public $timestamps = false;

    protected $table = 'comments';

    protected $fillable = [
        'domain_id',
        'name',
        'type',
        'modified_at',
        'account',
        'comment',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(PdnsDomain::class, 'domain_id');
    }
}

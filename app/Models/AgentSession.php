<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'configuration_id',
        'status',
        'metadata',
        'last_active_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_active_at' => 'datetime',
    ];

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(AgentConfiguration::class, 'configuration_id');
    }
}

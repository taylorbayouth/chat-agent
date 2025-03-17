<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'parameters',
    ];

    protected $casts = [
        'parameters' => 'array',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(AgentSession::class, 'configuration_id');
    }
}

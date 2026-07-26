<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsSnapshot extends Model
{
    protected $fillable = [
        'source',
        'payload',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'captured_at' => 'datetime',
        ];
    }

    public function scopeFromNas($query)
    {
        return $query->where('source', 'nas-n8n');
    }
}

<?php

namespace App\Models;

use App\Casts\AsAppTimezoneDateTime;
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
            'captured_at' => AsAppTimezoneDateTime::class,
        ];
    }

    public function scopeFromNas($query)
    {
        return $query->where('source', 'nas-n8n');
    }
}

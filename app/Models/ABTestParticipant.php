<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ABTestParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'ab_test_id',
        'visitor_id',
        'variant',
        'converted',
        'metadata',
    ];

    protected $casts = [
        'converted' => 'boolean',
        'metadata' => 'array',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(ABTest::class, 'ab_test_id');
    }

    public function markConverted(): void
    {
        $this->update(['converted' => true]);
    }

    public function addMetadata(array $data): void
    {
        $this->update([
            'metadata' => array_merge($this->metadata ?? [], $data),
        ]);
    }
}

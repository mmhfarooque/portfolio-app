<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ABTest extends Model
{
    use HasFactory;

    protected $table = 'ab_tests';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'status',
        'variants',
        'goal',
        'sample_size',
        'confidence_level',
        'winner_variant',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'variants' => 'array',
        'sample_size' => 'integer',
        'confidence_level' => 'float',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function participants(): HasMany
    {
        return $this->hasMany(ABTestParticipant::class, 'ab_test_id');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function start(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    public function complete(string $winner = null): void
    {
        $this->update([
            'status' => 'completed',
            'winner_variant' => $winner,
            'ended_at' => now(),
        ]);
    }

    public function getVariantNames(): array
    {
        return collect($this->variants)->pluck('name')->toArray();
    }

    public function getVariantByName(string $name): ?array
    {
        return collect($this->variants)->firstWhere('name', $name);
    }

    /**
     * Get participant count per variant.
     */
    public function getVariantCounts(): array
    {
        return $this->participants()
            ->selectRaw('variant, COUNT(*) as count')
            ->groupBy('variant')
            ->pluck('count', 'variant')
            ->toArray();
    }

    /**
     * Get conversion rate per variant.
     */
    public function getConversionRates(): array
    {
        $rates = [];

        foreach ($this->getVariantNames() as $variant) {
            $participants = $this->participants()->where('variant', $variant);
            $total = $participants->count();
            $converted = $participants->where('converted', true)->count();

            $rates[$variant] = [
                'total' => $total,
                'converted' => $converted,
                'rate' => $total > 0 ? round(($converted / $total) * 100, 2) : 0,
            ];
        }

        return $rates;
    }

    /**
     * Get total participant count.
     */
    public function getTotalParticipants(): int
    {
        return $this->participants()->count();
    }

    /**
     * Check if sample size reached.
     */
    public function hasSufficientSample(): bool
    {
        return $this->getTotalParticipants() >= $this->sample_size;
    }
}

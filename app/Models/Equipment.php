<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Equipment extends Model
{
    protected $table = 'equipment';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'brand',
        'model',
        'description',
        'image',
        'affiliate_link',
        'specifications',
        'acquired_date',
        'is_current',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'specifications' => 'array',
        'acquired_date' => 'date',
        'is_current' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * Get photos taken with this equipment (via EXIF matching).
     */
    public function getPhotosAttribute()
    {
        if (empty($this->brand) && empty($this->model)) {
            return collect();
        }

        $query = Photo::published();

        if ($this->type === 'camera') {
            $query->where(function ($q) {
                if ($this->brand) {
                    $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(exif_data, '$.Make')) LIKE ?", ["%{$this->brand}%"]);
                }
                if ($this->model) {
                    $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(exif_data, '$.Model')) LIKE ?", ["%{$this->model}%"]);
                }
            });
        } elseif ($this->type === 'lens') {
            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(exif_data, '$.LensModel')) LIKE ?", ["%{$this->model}%"]);
        }

        return $query->latest('captured_at')->get();
    }

    /**
     * Get photo count for this equipment.
     */
    public function getPhotoCountAttribute(): int
    {
        return $this->photos->count();
    }

    /**
     * Scope for current equipment.
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope for featured equipment.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get image URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return null;
    }

    /**
     * Get type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'camera' => 'Camera',
            'lens' => 'Lens',
            'accessory' => 'Accessory',
            'lighting' => 'Lighting',
            'software' => 'Software',
            default => ucfirst($this->type),
        };
    }
}

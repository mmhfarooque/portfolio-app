<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = [
        'translatable_type',
        'translatable_id',
        'locale',
        'field',
        'value',
    ];

    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get translation for a model, field, and locale.
     */
    public static function getFor(Model $model, string $field, string $locale): ?string
    {
        return static::where('translatable_type', get_class($model))
            ->where('translatable_id', $model->id)
            ->where('field', $field)
            ->where('locale', $locale)
            ->value('value');
    }

    /**
     * Set translation for a model, field, and locale.
     */
    public static function setFor(Model $model, string $field, string $locale, string $value): static
    {
        return static::updateOrCreate(
            [
                'translatable_type' => get_class($model),
                'translatable_id' => $model->id,
                'field' => $field,
                'locale' => $locale,
            ],
            ['value' => $value]
        );
    }

    /**
     * Get all translations for a model.
     */
    public static function allFor(Model $model): array
    {
        $translations = static::where('translatable_type', get_class($model))
            ->where('translatable_id', $model->id)
            ->get();

        $result = [];
        foreach ($translations as $translation) {
            $result[$translation->locale][$translation->field] = $translation->value;
        }

        return $result;
    }

    /**
     * Delete all translations for a model.
     */
    public static function deleteFor(Model $model): int
    {
        return static::where('translatable_type', get_class($model))
            ->where('translatable_id', $model->id)
            ->delete();
    }
}

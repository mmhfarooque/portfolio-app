<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Datetime cast that normalizes any timezone offset in the incoming value
 * to the application timezone before storage. Eloquent's native datetime
 * cast formats in the value's own timezone, silently dropping offsets.
 */
class AsAppTimezoneDateTime implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        return $value === null
            ? null
            : Carbon::parse($value, config('app.timezone'));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null
            ? null
            : Carbon::parse($value)->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }
}

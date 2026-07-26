<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnalyticsSnapshotRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Token verification happens in the VerifyNasToken middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'captured_at' => 'nullable|date',
            'gsc' => 'nullable|array',
            'gsc.clicks' => 'nullable|numeric',
            'gsc.impressions' => 'nullable|numeric',
            'gsc.ctr' => 'nullable|numeric',
            'gsc.position' => 'nullable|numeric',
            'gsc.topQueries' => 'nullable|array|max:25',
            'gsc.topPages' => 'nullable|array|max:25',
            'gsc.clicksOverTime' => 'nullable|array|max:120',
            'ga' => 'nullable|array',
            'ga.activeUsers' => 'nullable|numeric',
            'ga.sessions' => 'nullable|numeric',
            'ga.pageViews' => 'nullable|numeric',
            'ga.topPages' => 'nullable|array|max:25',
        ];
    }
}

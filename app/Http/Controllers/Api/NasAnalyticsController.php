<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnalyticsSnapshotRequest;
use App\Models\AnalyticsSnapshot;

class NasAnalyticsController extends Controller
{
    /**
     * Receive a GA/GSC analytics snapshot pushed by the NAS n8n workflow.
     */
    public function store(StoreAnalyticsSnapshotRequest $request)
    {
        $validated = $request->validated();

        $snapshot = AnalyticsSnapshot::create([
            'source' => 'nas-n8n',
            'payload' => collect($validated)->except('captured_at')->all(),
            'captured_at' => $validated['captured_at'] ?? now(),
        ]);

        // Keep a bounded history — 90 days is plenty for the dashboard.
        AnalyticsSnapshot::fromNas()
            ->where('created_at', '<', now()->subDays(90))
            ->delete();

        return response()->json(['ok' => true, 'id' => $snapshot->id], 201);
    }
}

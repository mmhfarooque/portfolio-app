<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LightroomSyncService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LightroomSyncController extends Controller
{
    protected LightroomSyncService $syncService;

    public function __construct(LightroomSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Display Lightroom sync page.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/LightroomSync/Index');
    }

    /**
     * Process uploaded XMP files.
     */
    public function process(Request $request): Response
    {
        $request->validate([
            'xmp_files' => 'required|array',
            'xmp_files.*' => 'file|max:1024', // Max 1MB per file
            'overwrite' => 'boolean',
            'sync_tags' => 'boolean',
        ]);

        $options = [
            'overwrite' => $request->boolean('overwrite'),
            'sync_tags' => $request->boolean('sync_tags', true),
        ];

        $results = $this->syncService->processXmpFiles(
            $request->file('xmp_files'),
            $options
        );

        return Inertia::render('Admin/LightroomSync/Results', [
            'results' => $results,
        ]);
    }

    /**
     * Preview XMP file contents.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'xmp_file' => 'required|file|max:1024',
        ]);

        $content = file_get_contents($request->file('xmp_file')->getRealPath());
        $data = $this->syncService->parseXmp($content);

        return response()->json($data);
    }
}

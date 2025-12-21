<?php

namespace App\Http\Controllers;

use App\Models\ClientSelection;
use App\Models\Gallery;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClientProofingController extends Controller
{
    /**
     * Toggle photo selection.
     */
    public function toggle(Request $request, Photo $photo): JsonResponse
    {
        $galleryId = $request->input('gallery_id');

        $isSelected = ClientSelection::toggleSelection($photo->id, $galleryId);
        $count = ClientSelection::getSelectionCount();

        return response()->json([
            'selected' => $isSelected,
            'count' => $count,
        ]);
    }

    /**
     * Check if photo is selected.
     */
    public function check(Photo $photo): JsonResponse
    {
        return response()->json([
            'selected' => ClientSelection::isSelected($photo->id),
        ]);
    }

    /**
     * Get all selections for the current session.
     */
    public function index(Request $request): \Inertia\Response
    {
        $selections = ClientSelection::forSession()
            ->with(['photo', 'gallery'])
            ->latest()
            ->get();

        $selectionCount = $selections->count();

        // Group by gallery if applicable
        $groupedSelections = $selections->groupBy(function ($selection) {
            return $selection->gallery_id ?? 0;
        })->map(function ($items, $galleryId) {
            $gallery = $items->first()->gallery;
            return [
                'gallery' => $gallery ? [
                    'id' => $gallery->id,
                    'name' => $gallery->name,
                ] : null,
                'photos' => $items->map(fn($s) => [
                    'id' => $s->id,
                    'photo' => [
                        'id' => $s->photo->id,
                        'title' => $s->photo->title,
                        'slug' => $s->photo->slug,
                        'thumbnail_path' => $s->photo->thumbnail_path,
                    ],
                    'created_at' => $s->created_at->format('M d, Y'),
                ]),
            ];
        })->values();

        return \Inertia\Inertia::render('Public/Client/Selections', [
            'selections' => $selections->map(fn($s) => [
                'id' => $s->id,
                'photo' => [
                    'id' => $s->photo->id,
                    'title' => $s->photo->title,
                    'slug' => $s->photo->slug,
                    'thumbnail_path' => $s->photo->thumbnail_path,
                ],
                'gallery' => $s->gallery ? ['name' => $s->gallery->name] : null,
            ]),
            'groupedSelections' => $groupedSelections,
            'selectionCount' => $selectionCount,
        ]);
    }

    /**
     * Get selection count for the current session.
     */
    public function count(): JsonResponse
    {
        return response()->json([
            'count' => ClientSelection::getSelectionCount(),
        ]);
    }

    /**
     * Clear all selections for the current session.
     */
    public function clear(): JsonResponse
    {
        ClientSelection::forSession()->delete();

        return response()->json([
            'success' => true,
            'count' => 0,
        ]);
    }

    /**
     * Export selections as a downloadable list.
     */
    public function export(Request $request)
    {
        $selections = ClientSelection::forSession()
            ->with(['photo', 'gallery'])
            ->get();

        if ($selections->isEmpty()) {
            return redirect()->route('client.selections')
                ->with('error', 'No photos selected to export.');
        }

        $format = $request->input('format', 'txt');

        if ($format === 'csv') {
            return $this->exportCsv($selections);
        }

        return $this->exportTxt($selections);
    }

    /**
     * Export selections as CSV.
     */
    protected function exportCsv($selections)
    {
        $filename = 'photo-selections-' . date('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($selections) {
            $file = fopen('php://output', 'w');

            // CSV header
            fputcsv($file, ['Photo Title', 'Filename', 'Gallery', 'Selected At']);

            foreach ($selections as $selection) {
                fputcsv($file, [
                    $selection->photo->title ?? 'Untitled',
                    basename($selection->photo->file_path ?? ''),
                    $selection->gallery->name ?? 'No Gallery',
                    $selection->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export selections as plain text.
     */
    protected function exportTxt($selections)
    {
        $filename = 'photo-selections-' . date('Y-m-d-His') . '.txt';

        $content = "Photo Selections\n";
        $content .= "Exported: " . date('Y-m-d H:i:s') . "\n";
        $content .= str_repeat('-', 50) . "\n\n";

        $grouped = $selections->groupBy(function ($selection) {
            return $selection->gallery->name ?? 'No Gallery';
        });

        foreach ($grouped as $galleryName => $items) {
            $content .= "Gallery: {$galleryName}\n";
            $content .= str_repeat('-', 30) . "\n";

            foreach ($items as $selection) {
                $content .= "- " . ($selection->photo->title ?? 'Untitled');
                $content .= " (" . basename($selection->photo->file_path ?? '') . ")\n";
            }
            $content .= "\n";
        }

        $content .= "\nTotal selected: " . $selections->count() . " photos";

        return response($content, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Send selections to photographer via email.
     */
    public function sendToPhotographer(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'nullable|string|max:2000',
        ]);

        $selections = ClientSelection::forSession()
            ->with(['photo', 'gallery'])
            ->get();

        if ($selections->isEmpty()) {
            return back()->with('error', 'No photos selected to send.');
        }

        // Get photographer email from settings
        $photographerEmail = \App\Models\Setting::getValue('contact_email', config('mail.from.address'));

        // Prepare photo list
        $photoList = $selections->map(function ($selection) {
            return [
                'title' => $selection->photo->title ?? 'Untitled',
                'filename' => basename($selection->photo->file_path ?? ''),
                'gallery' => $selection->gallery->name ?? 'No Gallery',
                'url' => route('photos.show', $selection->photo->slug),
            ];
        })->toArray();

        try {
            \Illuminate\Support\Facades\Mail::send([], [], function ($mail) use ($request, $photoList, $photographerEmail) {
                $subject = "Photo Selection from {$request->name}";

                $body = "New photo selection received!\n\n";
                $body .= "From: {$request->name} ({$request->email})\n";
                if ($request->message) {
                    $body .= "Message: {$request->message}\n";
                }
                $body .= "\n" . str_repeat('-', 50) . "\n\n";
                $body .= "Selected Photos (" . count($photoList) . " total):\n\n";

                foreach ($photoList as $photo) {
                    $body .= "- {$photo['title']} ({$photo['filename']})\n";
                    $body .= "  Gallery: {$photo['gallery']}\n";
                    $body .= "  View: {$photo['url']}\n\n";
                }

                $mail->to($photographerEmail)
                    ->replyTo($request->email, $request->name)
                    ->subject($subject)
                    ->text($body);
            });

            return back()->with('success', 'Your photo selections have been sent to the photographer!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send email. Please try again later.');
        }
    }
}

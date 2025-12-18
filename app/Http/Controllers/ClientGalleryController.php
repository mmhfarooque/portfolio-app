<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use Illuminate\Http\Request;

class ClientGalleryController extends Controller
{
    /**
     * View client gallery via access token.
     */
    public function view(string $token)
    {
        $gallery = Gallery::where('access_token', $token)
            ->with(['photos' => function ($query) {
                $query->where('status', 'published')->orderBy('sort_order');
            }])
            ->firstOrFail();

        // Check if expired
        if ($gallery->isExpired()) {
            return view('client-gallery.expired', compact('gallery'));
        }

        // Check if password protected and not already authenticated
        if ($gallery->isPasswordProtected() && !$gallery->hasAccess()) {
            return view('client-gallery.password', compact('gallery', 'token'));
        }

        // Record the view
        $gallery->recordView();

        return view('client-gallery.view', compact('gallery'));
    }

    /**
     * Verify password for client gallery.
     */
    public function verifyPassword(Request $request, string $token)
    {
        $gallery = Gallery::where('access_token', $token)->firstOrFail();

        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        if (!$gallery->verifyPassword($validated['password'])) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $gallery->grantAccess();

        return redirect()->route('client-gallery.view', $token);
    }

    /**
     * Download a photo from client gallery (if allowed).
     */
    public function download(string $token, int $photoId)
    {
        $gallery = Gallery::where('access_token', $token)->firstOrFail();

        // Check if expired
        if ($gallery->isExpired()) {
            abort(403, 'This gallery has expired.');
        }

        // Check if downloads are allowed
        if (!$gallery->allow_downloads) {
            abort(403, 'Downloads are not enabled for this gallery.');
        }

        // Check password if required
        if ($gallery->isPasswordProtected() && !$gallery->hasAccess()) {
            return redirect()->route('client-gallery.view', $token);
        }

        $photo = $gallery->photos()->where('id', $photoId)->firstOrFail();

        // Return the optimized file for download
        $filePath = storage_path('app/public/' . $photo->optimized_path);

        if (!file_exists($filePath)) {
            abort(404, 'Photo file not found.');
        }

        $filename = $photo->slug . '.' . pathinfo($filePath, PATHINFO_EXTENSION);

        return response()->download($filePath, $filename);
    }

    /**
     * Select/deselect a photo in client gallery.
     */
    public function toggleSelection(Request $request, string $token, int $photoId)
    {
        $gallery = Gallery::where('access_token', $token)->firstOrFail();

        // Check if expired
        if ($gallery->isExpired()) {
            return response()->json(['error' => 'Gallery has expired'], 403);
        }

        // Check if selections are allowed
        if (!$gallery->allow_selections) {
            return response()->json(['error' => 'Selections are not enabled'], 403);
        }

        // Check password if required
        if ($gallery->isPasswordProtected() && !$gallery->hasAccess()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $photo = $gallery->photos()->where('id', $photoId)->firstOrFail();

        // Get current selections from session
        $sessionKey = "client_selections_{$gallery->id}";
        $selections = session($sessionKey, []);

        $isSelected = in_array($photoId, $selections);

        if ($isSelected) {
            // Deselect
            $selections = array_values(array_diff($selections, [$photoId]));
        } else {
            // Check selection limit
            if ($gallery->selection_limit && count($selections) >= $gallery->selection_limit) {
                return response()->json([
                    'error' => "Selection limit of {$gallery->selection_limit} photos reached",
                    'limit_reached' => true,
                ], 400);
            }
            // Select
            $selections[] = $photoId;
        }

        session([$sessionKey => $selections]);

        return response()->json([
            'selected' => !$isSelected,
            'count' => count($selections),
            'limit' => $gallery->selection_limit,
        ]);
    }

    /**
     * Get current selections for client gallery.
     */
    public function getSelections(string $token)
    {
        $gallery = Gallery::where('access_token', $token)->firstOrFail();

        $sessionKey = "client_selections_{$gallery->id}";
        $selections = session($sessionKey, []);

        return response()->json([
            'selections' => $selections,
            'count' => count($selections),
            'limit' => $gallery->selection_limit,
        ]);
    }

    /**
     * Submit selections for client gallery.
     */
    public function submitSelections(Request $request, string $token)
    {
        $gallery = Gallery::where('access_token', $token)->firstOrFail();

        if ($gallery->isExpired()) {
            return back()->with('error', 'This gallery has expired.');
        }

        if (!$gallery->allow_selections) {
            return back()->with('error', 'Selections are not enabled for this gallery.');
        }

        $validated = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        $sessionKey = "client_selections_{$gallery->id}";
        $selections = session($sessionKey, []);

        if (empty($selections)) {
            return back()->with('error', 'Please select at least one photo.');
        }

        // Get selected photos
        $selectedPhotos = $gallery->photos()->whereIn('id', $selections)->get();

        // Store the selections (you could also create a separate ClientSelection model)
        $gallery->update([
            'client_name' => $validated['client_name'],
            'client_email' => $validated['client_email'],
            'client_notes' => $validated['notes'] ?? null,
        ]);

        // Send notification email to photographer
        $this->notifyPhotographer($gallery, $selectedPhotos, $validated);

        // Clear session
        session()->forget($sessionKey);

        return view('client-gallery.submitted', [
            'gallery' => $gallery,
            'selectedCount' => count($selections),
        ]);
    }

    /**
     * Notify photographer of client selections.
     */
    protected function notifyPhotographer(Gallery $gallery, $selectedPhotos, array $clientData): void
    {
        $contactEmail = \App\Models\Setting::get('contact_email');

        if (!$contactEmail) {
            return;
        }

        $photoTitles = $selectedPhotos->pluck('title')->implode(', ');

        \Mail::raw(
            "Client Selection Received\n\n" .
            "Gallery: {$gallery->name}\n" .
            "Client: {$clientData['client_name']} ({$clientData['client_email']})\n" .
            "Photos Selected: {$selectedPhotos->count()}\n" .
            "Selected Photos: {$photoTitles}\n\n" .
            "Notes: " . ($clientData['notes'] ?? 'None') . "\n\n" .
            "View gallery: " . route('admin.galleries.show', $gallery),
            function ($message) use ($contactEmail, $gallery, $clientData) {
                $message->to($contactEmail)
                    ->replyTo($clientData['client_email'], $clientData['client_name'])
                    ->subject("Client Selection: {$gallery->name}");
            }
        );
    }
}

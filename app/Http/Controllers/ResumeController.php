<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureResumeUnlocked;
use App\Services\Resume\ResumeRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ResumeController extends Controller
{
    public function __construct(private ResumeRepository $resume) {}

    /**
     * Reached only through EnsureResumeUnlocked.
     */
    public function show(): Response
    {
        $data = $this->resume->all();

        abort_if($data === null, 503, 'Resume content is not available.');

        return Inertia::render('Public/Resume/Show', [
            'resume' => $data,
        ]);
    }

    public function unlock(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string', 'max:200']]);

        $hash = config('resume.password_hash');

        if (! is_string($hash) || $hash === '' || ! Hash::check($request->input('password'), $hash)) {
            return back()->withErrors([
                'password' => 'That password did not work. Please check it and try again.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put(
            EnsureResumeUnlocked::SESSION_KEY,
            now()->addMinutes(config('resume.unlock_minutes'))->getTimestamp(),
        );

        return redirect('/resume');
    }
}

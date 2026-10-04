<?php

namespace App\Http\Middleware;

use App\Services\Resume\ResumeRepository;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the resume content. Until the visitor has unlocked it this
 * middleware answers with the locked view itself, so the controller that
 * loads the resume data is never reached and nothing private can leak into
 * the Inertia props or the SSR HTML.
 */
class EnsureResumeUnlocked
{
    public const SESSION_KEY = 'resume_unlocked_until';

    public function __construct(private ResumeRepository $resume) {}

    public static function isUnlocked(Request $request): bool
    {
        $until = (int) $request->session()->get(self::SESSION_KEY, 0);

        return $until > now()->getTimestamp();
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (self::isUnlocked($request)) {
            return $next($request);
        }

        $request->session()->forget(self::SESSION_KEY);

        return Inertia::render('Public/Resume/Locked', [
            'summary' => $this->resume->lockedSummary(),
            'hint' => config('resume.show_password_hint') ? config('resume.password_hint') : null,
        ])->toResponse($request);
    }
}

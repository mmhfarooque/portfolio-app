<?php

use App\Services\LoggingService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Exclude Stripe webhook from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);

        // Add referral tracking to web routes
        $middleware->web(append: [
            \App\Http\Middleware\TrackReferrals::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Log all exceptions to database
        $exceptions->report(function (Throwable $e) {
            // Skip logging for certain exception types
            $skipExceptions = [
                \Illuminate\Auth\AuthenticationException::class,
                \Illuminate\Session\TokenMismatchException::class,
                \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
                \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
            ];

            foreach ($skipExceptions as $skip) {
                if ($e instanceof $skip) {
                    return false; // Don't log but continue with default handling
                }
            }

            try {
                LoggingService::error(
                    'exception.unhandled',
                    $e->getMessage(),
                    $e
                );
            } catch (Throwable $loggingException) {
                // If logging fails, don't prevent the original exception from being handled
                // Fall back to standard Laravel logging
                \Log::error('Failed to log exception to database', [
                    'original_exception' => $e->getMessage(),
                    'logging_exception' => $loggingException->getMessage(),
                ]);
            }

            return false; // Continue with default exception handling
        });
    })->create();

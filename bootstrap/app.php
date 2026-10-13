<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\RefreshActiveUserEmotion;
use App\Http\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\EnsureUserIsActive::class,
            RefreshActiveUserEmotion::class,
        ]);

        // Set by JS (theme toggle) and read in Blade to render the right theme stylesheets up front
        $middleware->encryptCookies(except: ['theme']);

        // Guests go straight to the login page ('/'); route('login') is POST-only
        $middleware->redirectGuestsTo('/');

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'event.visitation' => \App\Http\Middleware\EnsureEventVisitation::class,
        ]);
        
        // No custom CSRF exceptions required (WhatsApp webhook removed)
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Login page left open until the session/CSRF token expired: show the form again instead of a 419 page
        // (TokenMismatchException reaches render callbacks already converted to an HttpException 419)
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 419 && $request->is('login')) {
                // fetch submit: send a fresh token so the page can retry without reloading
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Sesi telah berakhir.', 'token' => csrf_token()], 419);
                }
                return redirect('/')
                    ->withInput($request->except('password', '_token'))
                    ->withErrors(['email' => 'Sesi telah berakhir, silakan login kembali.']);
            }
        });
        // Too many failed attempts (throttle on POST /login)
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, \Illuminate\Http\Request $request) {
            if ($request->is('login')) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Terlalu banyak percobaan login. Coba lagi dalam 1 menit.'], 429);
                }
                return redirect('/')
                    ->withInput($request->except('password', '_token'))
                    ->withErrors(['email' => 'Terlalu banyak percobaan login. Coba lagi dalam 1 menit.']);
            }
        });
    })->create();

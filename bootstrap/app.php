<?php

use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\BlockBots::class,
            \App\Http\Middleware\BlockBotIps::class,
            \App\Http\Middleware\BlockVpn::class,
            \App\Http\Middleware\BlockIsp::class,
            \App\Http\Middleware\RedirectOneTimeIp::class,
            \App\Http\Middleware\BlockWrongEntry::class,
            \App\Http\Middleware\LogVisitors::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'telegram/webhook/*',
        ]);

        $middleware->alias([
            'role' => EnsureRole::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin', 'admin/*')) {
                return route('admin.login');
            }

            $publicToken = $request->route('publicToken');
            if (is_string($publicToken) && strlen($publicToken) >= 32) {
                return route('user.login', ['publicToken' => $publicToken]);
            }

            return \App\Support\AppRedirect::loginEntryUrl();
        });

        $middleware->redirectUsersTo(function (Request $request) {
            if (Auth::guard('web')->check()) {
                return route('admin.dashboard');
            }

            $publicToken = $request->route('publicToken');
            if (is_string($publicToken) && strlen($publicToken) >= 32) {
                $session = \App\Support\AccessSessionResolver::current($request);
                if ($session && \App\Support\AccessSessionResolver::isActive($session)) {
                    return route(
                        $session->routeForStatus() ?? 'waiting',
                        ['publicToken' => $publicToken]
                    );
                }

                return route('waiting', ['publicToken' => $publicToken]);
            }

            return \App\Support\AppRedirect::loginEntryUrl();
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class isActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->status !== 'active') {
            Auth::logout();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Your account is inactive. Please contact support.',
                ]);
        }

        if (Auth::check()) {
            Cache::put(
                'user_active_' . Auth::id(),
                true,
                now()->addMinutes(3)
            );
        }
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Support\InstallState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (InstallState::isInstalled()) {
            return $next($request);
        }

        if ($request->is('install') || $request->is('install/*') || $request->is('up')) {
            return $next($request);
        }

        return redirect()->route('install.index');
    }
}

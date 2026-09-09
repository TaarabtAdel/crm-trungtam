<?php

namespace App\Http\Middleware;

use App\Support\InstallState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventInstallWhenInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (InstallState::isInstalled()) {
            return redirect()->route('login')
                ->with('error', 'Hệ thống đã được cài đặt. Đăng nhập để tiếp tục.');
        }

        return $next($request);
    }
}

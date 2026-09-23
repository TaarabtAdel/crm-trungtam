<?php

namespace App\Http\Middleware;

use App\Support\CurrentBranch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceUserBranch
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            CurrentBranch::syncFromUser();
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! setting('maintenance_mode', false)) {
            return $next($request);
        }

        if ($request->is('admin') || $request->is('admin/*') || $request->is('up')) {
            return $next($request);
        }

        abort(503, 'The store is temporarily closed for maintenance.');
    }
}

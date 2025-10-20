<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogAuthUser
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            Log::info('User autenticado', [
                'user_id' => $request->user()->id,
                'user_email' => $request->user()->email,
                'user_name' => $request->user()->name,
                'is_super_admin' => $request->user()->isSuperAdmin(),
                'tenant_id' => $request->user()->tenant_id,
                'route' => $request->path(),
                'method' => $request->method(),
            ]);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super admin não precisa de verificação
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        // Verifica se o usuário tem tenant e se está ativo
        if ($user && $user->tenant_id) {
            $tenant = $user->tenant;

            if ($tenant && !$tenant->is_active) {
                auth()->logout();
                return redirect()->route('login')
                    ->with('error', 'Seu tenant está inativo. Entre em contato com o suporte.');
            }
        }

        return $next($request);
    }
}

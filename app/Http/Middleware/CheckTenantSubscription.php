<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantSubscription
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

        // Verifica se o usuário tem tenant
        if (!$user || !$user->tenant_id) {
            abort(403, 'Usuário não possui tenant associado.');
        }

        $tenant = $user->tenant;

        // Verifica se o tenant está ativo
        if (!$tenant->is_active) {
            abort(403, 'Tenant inativo. Entre em contato com o suporte.');
        }

        // Verifica se tem assinatura ativa
        $subscription = $tenant->activeSubscription;

        if (!$subscription) {
            return redirect()->route('subscription.expired')
                ->with('error', 'Sua assinatura expirou. Renove para continuar usando o sistema.');
        }

        // Verifica se está em trial e se expirou
        if ($subscription->onTrial() && $subscription->trial_ends_at->isPast()) {
            $subscription->update(['status' => 'expired']);
            return redirect()->route('subscription.expired')
                ->with('error', 'Seu período de teste expirou. Assine um plano para continuar.');
        }

        // Verifica se a assinatura está expirada
        if ($subscription->isExpired()) {
            $subscription->update(['status' => 'expired']);
            return redirect()->route('subscription.expired')
                ->with('error', 'Sua assinatura expirou. Renove para continuar usando o sistema.');
        }

        return $next($request);
    }
}

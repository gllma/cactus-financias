<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureObservabilityAllowlisted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $allowlist = config('observability.allowlist', []);

        if ($user === null || !in_array($user->email, $allowlist, true)) {
            abort(Response::HTTP_FORBIDDEN, 'Acesso ao painel de observabilidade não autorizado.');
        }

        return $next($request);
    }
}

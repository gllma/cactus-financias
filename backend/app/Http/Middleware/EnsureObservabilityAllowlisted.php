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
        $identifiers = [
            (string) ($user?->email ?? ''),
            (string) ($user?->getAuthIdentifier() ?? ''),
        ];

        if ($user === null || !$this->hasAllowlistedIdentifier($allowlist, $identifiers)) {
            abort(Response::HTTP_FORBIDDEN, 'Acesso ao painel de observabilidade não autorizado.');
        }

        return $next($request);
    }

    /**
     * @param array<int, string> $allowlist
     * @param array<int, string> $identifiers
     */
    private function hasAllowlistedIdentifier(array $allowlist, array $identifiers): bool
    {
        foreach ($identifiers as $identifier) {
            if ($identifier !== '' && in_array($identifier, $allowlist, true)) {
                return true;
            }
        }

        return false;
    }
}

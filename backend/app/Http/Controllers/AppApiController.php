<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AppService;
use RuntimeException;

class AppApiController
{
    public function __construct(private readonly AppService $service)
    {
    }

    public function bearerToken(): string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (preg_match('/Bearer\s+(.+)/i', $header, $matches) === 1) {
            return trim($matches[1]);
        }

        return '';
    }

    public function context(): array
    {
        $token = $this->bearerToken();
        $headerEmail = (string) ($_SERVER['HTTP_X_USER_EMAIL'] ?? '');
        $headerName = (string) ($_SERVER['HTTP_X_USER_NAME'] ?? '');
        $headerSpaceId = isset($_SERVER['HTTP_X_SPACE_ID']) ? (int) $_SERVER['HTTP_X_SPACE_ID'] : null;

        return $this->service->resolveContext($token, $headerEmail, $headerName, $headerSpaceId);
    }

    public function requireAuth(): array
    {
        $token = $this->bearerToken();
        if ($token === '') {
            throw new RuntimeException('Não autenticado.');
        }

        $context = $this->service->contextFromToken($token);
        if ($context === null) {
            throw new RuntimeException('Token inválido.');
        }

        return $context;
    }
}

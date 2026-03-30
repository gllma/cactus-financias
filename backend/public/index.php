<?php

declare(strict_types=1);

use App\Http\Controllers\AppApiController;
use App\Infrastructure\DatabaseConnection;
use App\Repositories\AppRepository;
use App\Services\AppService;
require_once __DIR__ . '/../app/Infrastructure/DatabaseConnection.php';
require_once __DIR__ . '/../app/Repositories/AppRepository.php';
require_once __DIR__ . '/../app/Services/AppService.php';
require_once __DIR__ . '/../app/Http/Controllers/AppApiController.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-User-Email, X-User-Name, X-Space-Id');
header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function payload(): array
{
    return json_decode((string) file_get_contents('php://input'), true) ?: [];
}

function statusFromMessage(string $message): int
{
    if (str_contains($message, 'Não autenticado') || str_contains($message, 'Token inválido')) {
        return 401;
    }

    if (str_contains($message, 'não autorizado') || str_contains($message, 'não participa') || str_contains($message, 'Somente o dono')) {
        return 403;
    }

    if (str_contains($message, 'não encontrado')) {
        return 404;
    }

    if (
        str_contains($message, 'obrigatório') ||
        str_contains($message, 'inválido') ||
        str_contains($message, 'não pode') ||
        str_contains($message, 'deve estar entre') ||
        str_contains($message, 'insuficiente')
    ) {
        return 422;
    }

    return 500;
}

try {
    $repository = new AppRepository(DatabaseConnection::make());
    $service = new AppService($repository);
    $controller = new AppApiController($service);

    if ($uri === '/health') {
        jsonResponse(['status' => 'ok', 'service' => 'cactus-financias-backend', 'database' => 'postgresql']);
    }

    if ($uri === '/api/auth/login' && $method === 'POST') {
        $data = payload();
        $result = $service->login(
            trim((string) ($data['email'] ?? '')),
            trim((string) ($data['name'] ?? 'Usuário')),
            (string) ($data['password'] ?? ''),
        );

        jsonResponse(['message' => 'Login realizado com sucesso.', 'data' => $result]);
    }

    $authContext = null;
    if (str_starts_with($uri, '/api') && !in_array($uri, ['/api/auth/login'], true)) {
        $authContext = $controller->requireAuth();
    }

    $context = $controller->context();

    if ($uri === '/api/auth/me' && $method === 'GET') {
        jsonResponse(['data' => ['email' => $context['email'], 'name' => $context['name'], 'active_space_id' => $context['active_space_id']]]);
    }

    if ($uri === '/api/auth/switch-space' && $method === 'POST') {
        $data = payload();
        $spaceId = (int) ($data['space_id'] ?? 0);
        if ($spaceId <= 0) {
            throw new RuntimeException('space_id inválido.');
        }

        $service->switchSpace($authContext['token'], $spaceId);
        jsonResponse(['message' => 'Espaço ativo atualizado com sucesso.', 'data' => ['active_space_id' => $spaceId]]);
    }

    if ($uri === '/api/profile/theme' && $method === 'GET') {
        jsonResponse(['data' => ['theme' => $service->theme($context['email'])]]);
    }

    if ($uri === '/api/profile/theme' && $method === 'PATCH') {
        $data = payload();
        $service->updateTheme($context['email'], (string) ($data['theme'] ?? ''));
        jsonResponse(['message' => 'Preferência de tema atualizada com sucesso.', 'data' => ['theme' => (string) ($data['theme'] ?? '')]]);
    }

    if ($uri === '/api/spaces' && $method === 'GET') {
        jsonResponse(['data' => $service->listSpaces($context['email'])]);
    }

    if ($uri === '/api/spaces' && $method === 'POST') {
        $data = payload();
        $space = $service->createSpace($context['email'], trim((string) ($data['name'] ?? '')), $authContext['token'] ?? '');
        jsonResponse(['message' => 'Espaço criado com sucesso.', 'data' => $space], 201);
    }

    if (preg_match('#^/api/spaces/(\d+)/invite$#', $uri, $matches) === 1 && $method === 'POST') {
        $data = payload();
        $service->inviteToSpace((int) $matches[1], $context['email'], trim((string) ($data['email'] ?? '')), trim((string) ($data['role'] ?? 'member')));
        jsonResponse(['message' => 'Convite enviado com sucesso.']);
    }

    if ($uri === '/api/infra/observability/summary' && $method === 'GET') {
        $periodMinutes = (int) ($_GET['period_minutes'] ?? 60);
        $summary = $service->observabilitySummary($context['email'], $periodMinutes);
        jsonResponse([
            'data' => [
                'failed_jobs' => $summary['failed_jobs'],
                'pending_jobs' => $summary['pending_jobs'],
                'recent_exceptions' => $summary['recent_exceptions'],
                'total_users' => $summary['total_users'],
                'dark_theme_users' => $summary['dark_theme_users'],
                'simulated_uptime_percent' => $summary['simulated_uptime_percent'],
            ],
            'meta' => [
                'generated_at' => gmdate('c'),
                'period_minutes' => $summary['period_minutes'],
            ],
        ]);
    }

    if ($uri === '/api/vaults' && $method === 'GET') {
        jsonResponse(['data' => $service->listVaults((int) $context['active_space_id'])]);
    }

    if ($uri === '/api/vaults' && $method === 'POST') {
        $data = payload();
        $vault = $service->createVault(
            $context['email'],
            (int) $context['active_space_id'],
            trim((string) ($data['name'] ?? '')),
            (float) ($data['target_amount'] ?? 0),
        );
        jsonResponse(['message' => 'Cofre criado com sucesso.', 'data' => $vault], 201);
    }

    if ($uri === '/api/vaults/insights' && $method === 'GET') {
        jsonResponse(['data' => $service->vaultInsights((int) $context['active_space_id'])]);
    }

    if ($uri === '/api/transactions/recent' && $method === 'GET') {
        jsonResponse(['data' => $service->recentTransactions((int) $context['active_space_id'], (int) ($_GET['limit'] ?? 10))]);
    }

    if (preg_match('#^/api/vaults/(\d+)/transactions$#', $uri, $matches) === 1 && $method === 'GET') {
        jsonResponse(['data' => $service->vaultDetail((int) $context['active_space_id'], (int) $matches[1])]);
    }

    if (preg_match('#^/api/vaults/(\d+)/transactions$#', $uri, $matches) === 1 && $method === 'POST') {
        $data = payload();
        $transaction = $service->addVaultTransaction(
            (int) $context['active_space_id'],
            (int) $matches[1],
            (string) ($data['type'] ?? ''),
            (float) ($data['amount'] ?? 0),
            trim((string) ($data['category'] ?? 'geral')),
            trim((string) ($data['description'] ?? '')),
        );

        jsonResponse(['message' => 'Movimentação registrada com sucesso.', 'data' => $transaction], 201);
    }

    if ($uri === '/' && $method === 'GET') {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html><body><h1>Cactus Financias Backend</h1><p>Acesse /health para validação.</p></body></html>';
        exit;
    }

    jsonResponse(['message' => 'Rota não encontrada'], 404);
} catch (RuntimeException $exception) {
    jsonResponse(['message' => $exception->getMessage()], statusFromMessage($exception->getMessage()));
} catch (Throwable $exception) {
    jsonResponse(['message' => 'Erro interno no servidor.', 'error' => $exception->getMessage()], 500);
}

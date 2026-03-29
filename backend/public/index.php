<?php

declare(strict_types=1);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$storageDir = __DIR__ . '/../storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0777, true);
}

$database = new PDO('sqlite:' . $storageDir . '/cactus_financias.sqlite');
$database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$database->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT UNIQUE, name TEXT, theme_preference TEXT NOT NULL DEFAULT "light")');

$userEmail = $_SERVER['HTTP_X_USER_EMAIL'] ?? 'admin@cactus.com';
$userName = $_SERVER['HTTP_X_USER_NAME'] ?? 'Maria Silva';

$insert = $database->prepare('INSERT OR IGNORE INTO users (email, name, theme_preference) VALUES (:email, :name, :theme)');
$insert->execute(['email' => $userEmail, 'name' => $userName, 'theme' => 'light']);

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

if ($uri === '/health') {
    jsonResponse([
        'status' => 'ok',
        'service' => 'cactus-financias-backend',
    ]);
}

if ($uri === '/api/profile/theme' && $method === 'GET') {
    $query = $database->prepare('SELECT theme_preference FROM users WHERE email = :email LIMIT 1');
    $query->execute(['email' => $userEmail]);
    $theme = (string) ($query->fetchColumn() ?: 'light');

    jsonResponse(['data' => ['theme' => $theme]]);
}

if ($uri === '/api/profile/theme' && $method === 'PATCH') {
    $payload = json_decode((string) file_get_contents('php://input'), true);
    $theme = $payload['theme'] ?? null;

    if (!in_array($theme, ['light', 'dark'], true)) {
        jsonResponse(['message' => 'Tema inválido.'], 422);
    }

    $update = $database->prepare('UPDATE users SET theme_preference = :theme WHERE email = :email');
    $update->execute(['theme' => $theme, 'email' => $userEmail]);

    jsonResponse([
        'message' => 'Preferência de tema atualizada com sucesso.',
        'data' => ['theme' => $theme],
    ]);
}

if ($uri === '/api/infra/observability/summary' && $method === 'GET') {
    $allowlistRaw = getenv('OBSERVABILITY_ALLOWLIST') ?: 'admin@cactus.com,1';
    $allowlist = array_filter(array_map('trim', explode(',', $allowlistRaw)));

    $query = $database->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $query->execute(['email' => $userEmail]);
    $userId = (string) ($query->fetchColumn() ?: '');

    if (!in_array($userEmail, $allowlist, true) && !in_array($userId, $allowlist, true)) {
        jsonResponse(['message' => 'Acesso ao painel de observabilidade não autorizado.'], 403);
    }

    $periodMinutes = (int) ($_GET['period_minutes'] ?? 60);
    if ($periodMinutes < 5 || $periodMinutes > 1440) {
        jsonResponse(['message' => 'period_minutes deve estar entre 5 e 1440.'], 422);
    }

    jsonResponse([
        'data' => [
            'failed_jobs' => 2,
            'pending_jobs' => 5,
            'recent_exceptions' => 1,
        ],
        'meta' => [
            'generated_at' => gmdate('c'),
            'period_minutes' => $periodMinutes,
        ],
    ]);
}

if ($uri === '/' && $method === 'GET') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><body><h1>Cactus Financias Backend</h1><p>Acesse /health para validação.</p></body></html>';
    exit;
}

jsonResponse(['message' => 'Rota não encontrada'], 404);

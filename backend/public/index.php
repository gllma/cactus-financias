<?php

declare(strict_types=1);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type, X-User-Email, X-User-Name');
header('Access-Control-Allow-Methods: GET, PATCH, OPTIONS');

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$storageDir = __DIR__ . '/../storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0777, true);
}

$database = new PDO('sqlite:' . $storageDir . '/cactus_financias.sqlite');
$database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$database->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT UNIQUE, name TEXT, theme_preference TEXT NOT NULL DEFAULT "light")');
$database->exec('CREATE TABLE IF NOT EXISTS vaults (id INTEGER PRIMARY KEY AUTOINCREMENT, user_email TEXT NOT NULL, name TEXT NOT NULL, target_amount REAL DEFAULT 0, created_at TEXT NOT NULL)');
$database->exec('CREATE TABLE IF NOT EXISTS vault_transactions (id INTEGER PRIMARY KEY AUTOINCREMENT, vault_id INTEGER NOT NULL, type TEXT NOT NULL, amount REAL NOT NULL, description TEXT, created_at TEXT NOT NULL)');

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

    $usersCount = (int) $database->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $darkThemeUsers = (int) $database->query("SELECT COUNT(*) FROM users WHERE theme_preference = 'dark'")->fetchColumn();

    jsonResponse([
        'data' => [
            'failed_jobs' => 2,
            'pending_jobs' => 5,
            'recent_exceptions' => 1,
            'total_users' => $usersCount,
            'dark_theme_users' => $darkThemeUsers,
            'simulated_uptime_percent' => 99.95,
        ],
        'meta' => [
            'generated_at' => gmdate('c'),
            'period_minutes' => $periodMinutes,
        ],
    ]);
}

if ($uri === '/api/vaults' && $method === 'GET') {
    $query = $database->prepare(
        "SELECT v.id, v.name, v.target_amount, v.created_at,
                COALESCE(SUM(CASE WHEN t.type = 'deposit' THEN t.amount WHEN t.type = 'withdraw' THEN -t.amount ELSE 0 END), 0) AS balance
         FROM vaults v
         LEFT JOIN vault_transactions t ON t.vault_id = v.id
         WHERE v.user_email = :email
         GROUP BY v.id
         ORDER BY v.id DESC"
    );
    $query->execute(['email' => $userEmail]);
    $vaults = $query->fetchAll(PDO::FETCH_ASSOC) ?: [];

    jsonResponse(['data' => $vaults]);
}

if ($uri === '/api/vaults' && $method === 'POST') {
    $payload = json_decode((string) file_get_contents('php://input'), true);
    $name = trim((string) ($payload['name'] ?? ''));
    $targetAmount = (float) ($payload['target_amount'] ?? 0);

    if ($name === '') {
        jsonResponse(['message' => 'O nome do cofre é obrigatório.'], 422);
    }

    if ($targetAmount < 0) {
        jsonResponse(['message' => 'target_amount não pode ser negativo.'], 422);
    }

    $createdAt = gmdate('c');
    $insert = $database->prepare('INSERT INTO vaults (user_email, name, target_amount, created_at) VALUES (:email, :name, :target, :created_at)');
    $insert->execute([
        'email' => $userEmail,
        'name' => $name,
        'target' => $targetAmount,
        'created_at' => $createdAt,
    ]);

    jsonResponse([
        'message' => 'Cofre criado com sucesso.',
        'data' => [
            'id' => (int) $database->lastInsertId(),
            'name' => $name,
            'target_amount' => $targetAmount,
            'balance' => 0,
            'created_at' => $createdAt,
        ],
    ], 201);
}

if (preg_match('#^/api/vaults/(\d+)/transactions$#', $uri, $matches) === 1) {
    $vaultId = (int) $matches[1];
    $vaultQuery = $database->prepare('SELECT id, name, target_amount, created_at FROM vaults WHERE id = :id AND user_email = :email LIMIT 1');
    $vaultQuery->execute(['id' => $vaultId, 'email' => $userEmail]);
    $vault = $vaultQuery->fetch(PDO::FETCH_ASSOC);

    if (!$vault) {
        jsonResponse(['message' => 'Cofre não encontrado.'], 404);
    }

    if ($method === 'GET') {
        $transactionsQuery = $database->prepare('SELECT id, type, amount, description, created_at FROM vault_transactions WHERE vault_id = :vault_id ORDER BY id DESC');
        $transactionsQuery->execute(['vault_id' => $vaultId]);
        $transactions = $transactionsQuery->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $balanceQuery = $database->prepare("SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount WHEN type = 'withdraw' THEN -amount ELSE 0 END), 0) FROM vault_transactions WHERE vault_id = :vault_id");
        $balanceQuery->execute(['vault_id' => $vaultId]);
        $balance = (float) $balanceQuery->fetchColumn();

        jsonResponse([
            'data' => [
                'vault' => [
                    'id' => (int) $vault['id'],
                    'name' => $vault['name'],
                    'target_amount' => (float) $vault['target_amount'],
                    'balance' => $balance,
                    'created_at' => $vault['created_at'],
                ],
                'transactions' => $transactions,
            ],
        ]);
    }

    if ($method === 'POST') {
        $payload = json_decode((string) file_get_contents('php://input'), true);
        $type = $payload['type'] ?? null;
        $amount = (float) ($payload['amount'] ?? 0);
        $description = trim((string) ($payload['description'] ?? ''));

        if (!in_array($type, ['deposit', 'withdraw'], true)) {
            jsonResponse(['message' => 'Tipo de movimentação inválido.'], 422);
        }

        if ($amount <= 0) {
            jsonResponse(['message' => 'amount deve ser maior que zero.'], 422);
        }

        $balanceQuery = $database->prepare("SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount WHEN type = 'withdraw' THEN -amount ELSE 0 END), 0) FROM vault_transactions WHERE vault_id = :vault_id");
        $balanceQuery->execute(['vault_id' => $vaultId]);
        $currentBalance = (float) $balanceQuery->fetchColumn();

        if ($type === 'withdraw' && $amount > $currentBalance) {
            jsonResponse(['message' => 'Saldo insuficiente para saque.'], 422);
        }

        $createdAt = gmdate('c');
        $insert = $database->prepare('INSERT INTO vault_transactions (vault_id, type, amount, description, created_at) VALUES (:vault_id, :type, :amount, :description, :created_at)');
        $insert->execute([
            'vault_id' => $vaultId,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'created_at' => $createdAt,
        ]);

        $newBalance = $type === 'deposit' ? $currentBalance + $amount : $currentBalance - $amount;
        jsonResponse([
            'message' => 'Movimentação registrada com sucesso.',
            'data' => [
                'id' => (int) $database->lastInsertId(),
                'vault_id' => $vaultId,
                'type' => $type,
                'amount' => $amount,
                'description' => $description,
                'created_at' => $createdAt,
                'balance' => $newBalance,
            ],
        ], 201);
    }
}

if ($uri === '/' && $method === 'GET') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><body><h1>Cactus Financias Backend</h1><p>Acesse /health para validação.</p></body></html>';
    exit;
}

jsonResponse(['message' => 'Rota não encontrada'], 404);

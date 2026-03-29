<?php

declare(strict_types=1);

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

function envOr(string $key, string $default): string
{
    $value = getenv($key);
    if ($value === false || trim($value) === '') {
        return $default;
    }

    return $value;
}

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

try {
    $dbHost = envOr('DB_HOST', 'db');
    $dbPort = envOr('DB_PORT', '5432');
    $dbName = envOr('DB_DATABASE', 'cactus_financias');
    $dbUser = envOr('DB_USERNAME', 'cactus');
    $dbPassword = envOr('DB_PASSWORD', 'cactus');

    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $dbHost, $dbPort, $dbName);
    $database = new PDO($dsn, $dbUser, $dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $exception) {
    jsonResponse([
        'message' => 'Falha ao conectar no banco de dados PostgreSQL.',
        'error' => $exception->getMessage(),
    ], 500);
}

$database->exec("CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    name VARCHAR(190) NOT NULL,
    theme_preference VARCHAR(20) NOT NULL DEFAULT 'light'
)");

$database->exec("CREATE TABLE IF NOT EXISTS spaces (
    id SERIAL PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    owner_email VARCHAR(190) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL
)");

$database->exec("CREATE TABLE IF NOT EXISTS space_members (
    id SERIAL PRIMARY KEY,
    space_id INTEGER NOT NULL,
    user_email VARCHAR(190) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'member',
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    created_at TIMESTAMPTZ NOT NULL,
    UNIQUE (space_id, user_email)
)");

$database->exec("CREATE TABLE IF NOT EXISTS auth_tokens (
    id SERIAL PRIMARY KEY,
    token VARCHAR(255) NOT NULL UNIQUE,
    user_email VARCHAR(190) NOT NULL,
    active_space_id INTEGER,
    created_at TIMESTAMPTZ NOT NULL
)");

$database->exec("CREATE TABLE IF NOT EXISTS vaults (
    id SERIAL PRIMARY KEY,
    user_email VARCHAR(190) NOT NULL,
    space_id INTEGER,
    name VARCHAR(190) NOT NULL,
    target_amount NUMERIC(14,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL
)");

$database->exec("CREATE TABLE IF NOT EXISTS vault_transactions (
    id SERIAL PRIMARY KEY,
    vault_id INTEGER NOT NULL,
    type VARCHAR(20) NOT NULL,
    amount NUMERIC(14,2) NOT NULL,
    category VARCHAR(80) NOT NULL DEFAULT 'geral',
    description TEXT,
    created_at TIMESTAMPTZ NOT NULL
)");

function ensurePersonalSpace(PDO $database, string $userEmail): int
{
    $spaceName = 'Espaço de ' . $userEmail;
    $query = $database->prepare('SELECT id FROM spaces WHERE owner_email = :email LIMIT 1');
    $query->execute(['email' => $userEmail]);
    $spaceId = (int) ($query->fetchColumn() ?: 0);

    if ($spaceId === 0) {
        $insert = $database->prepare('INSERT INTO spaces (name, owner_email, created_at) VALUES (:name, :email, :created_at) RETURNING id');
        $insert->execute(['name' => $spaceName, 'email' => $userEmail, 'created_at' => gmdate('c')]);
        $spaceId = (int) $insert->fetchColumn();
    }

    $membership = $database->prepare("INSERT INTO space_members (space_id, user_email, role, status, created_at)
        VALUES (:space_id, :email, :role, :status, :created_at)
        ON CONFLICT (space_id, user_email) DO NOTHING");
    $membership->execute([
        'space_id' => $spaceId,
        'email' => $userEmail,
        'role' => 'owner',
        'status' => 'active',
        'created_at' => gmdate('c'),
    ]);

    return $spaceId;
}

function resolveBearerToken(): string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $header, $matches) === 1) {
        return trim($matches[1]);
    }

    return '';
}

function requireAuthenticatedUser(PDO $database): array
{
    $token = resolveBearerToken();
    if ($token === '') {
        jsonResponse(['message' => 'Não autenticado.'], 401);
    }

    $query = $database->prepare('SELECT user_email, active_space_id FROM auth_tokens WHERE token = :token LIMIT 1');
    $query->execute(['token' => $token]);
    $row = $query->fetch();
    if (!$row) {
        jsonResponse(['message' => 'Token inválido.'], 401);
    }

    return [
        'token' => $token,
        'email' => (string) $row['user_email'],
        'active_space_id' => $row['active_space_id'] !== null ? (int) $row['active_space_id'] : null,
    ];
}

if ($uri === '/health') {
    jsonResponse([
        'status' => 'ok',
        'service' => 'cactus-financias-backend',
        'database' => 'postgresql',
    ]);
}

if ($uri === '/api/auth/login' && $method === 'POST') {
    $payload = json_decode((string) file_get_contents('php://input'), true);
    $email = trim((string) ($payload['email'] ?? ''));
    $name = trim((string) ($payload['name'] ?? 'Usuário'));
    $password = (string) ($payload['password'] ?? '');

    if ($email === '' || $password === '') {
        jsonResponse(['message' => 'E-mail e senha são obrigatórios.'], 422);
    }

    $insert = $database->prepare("INSERT INTO users (email, name, theme_preference)
        VALUES (:email, :name, :theme)
        ON CONFLICT (email) DO NOTHING");
    $insert->execute(['email' => $email, 'name' => $name, 'theme' => 'light']);

    $update = $database->prepare('UPDATE users SET name = :name WHERE email = :email');
    $update->execute(['name' => $name, 'email' => $email]);

    $spaceId = ensurePersonalSpace($database, $email);
    $token = bin2hex(random_bytes(24));
    $tokenInsert = $database->prepare('INSERT INTO auth_tokens (token, user_email, active_space_id, created_at) VALUES (:token, :email, :active_space_id, :created_at)');
    $tokenInsert->execute([
        'token' => $token,
        'email' => $email,
        'active_space_id' => $spaceId,
        'created_at' => gmdate('c'),
    ]);

    jsonResponse([
        'message' => 'Login realizado com sucesso.',
        'data' => [
            'token' => $token,
            'email' => $email,
            'name' => $name,
            'active_space_id' => $spaceId,
        ],
    ]);
}

$tokenContext = null;
$incomingToken = resolveBearerToken();
$userEmail = $_SERVER['HTTP_X_USER_EMAIL'] ?? '';
$userName = $_SERVER['HTTP_X_USER_NAME'] ?? '';

if ($incomingToken !== '') {
    $tokenQuery = $database->prepare('SELECT user_email, active_space_id FROM auth_tokens WHERE token = :token LIMIT 1');
    $tokenQuery->execute(['token' => $incomingToken]);
    $tokenContext = $tokenQuery->fetch() ?: null;
    if (!$tokenContext) {
        jsonResponse(['message' => 'Token inválido.'], 401);
    }

    $userEmail = (string) $tokenContext['user_email'];
    $nameQuery = $database->prepare('SELECT name FROM users WHERE email = :email LIMIT 1');
    $nameQuery->execute(['email' => $userEmail]);
    $userName = (string) ($nameQuery->fetchColumn() ?: $userName);
}

if ($userEmail === '') {
    $userEmail = 'admin@cactus.com';
}

if ($userName === '') {
    $userName = 'Maria Silva';
}

$insertUser = $database->prepare("INSERT INTO users (email, name, theme_preference)
    VALUES (:email, :name, :theme)
    ON CONFLICT (email) DO NOTHING");
$insertUser->execute(['email' => $userEmail, 'name' => $userName, 'theme' => 'light']);

$personalSpaceId = ensurePersonalSpace($database, $userEmail);
$backfillVaults = $database->prepare('UPDATE vaults SET space_id = :space_id WHERE user_email = :email AND (space_id IS NULL OR space_id = 0)');
$backfillVaults->execute(['space_id' => $personalSpaceId, 'email' => $userEmail]);
$activeSpaceId = (int) ($_SERVER['HTTP_X_SPACE_ID'] ?? ($tokenContext['active_space_id'] ?? $personalSpaceId));

$memberQuery = $database->prepare("SELECT COUNT(*) FROM space_members WHERE space_id = :space_id AND user_email = :email AND status IN ('active', 'invited')");
$memberQuery->execute(['space_id' => $activeSpaceId, 'email' => $userEmail]);
if ((int) $memberQuery->fetchColumn() === 0) {
    $activeSpaceId = $personalSpaceId;
}

if ($tokenContext && (int) ($tokenContext['active_space_id'] ?? 0) !== $activeSpaceId) {
    $updateToken = $database->prepare('UPDATE auth_tokens SET active_space_id = :space_id WHERE token = :token');
    $updateToken->execute(['space_id' => $activeSpaceId, 'token' => $incomingToken]);
}

if (str_starts_with($uri, '/api') && !in_array($uri, ['/api/auth/login'], true) && $incomingToken === '') {
    jsonResponse(['message' => 'Não autenticado.'], 401);
}

if ($uri === '/api/auth/me' && $method === 'GET') {
    jsonResponse([
        'data' => [
            'email' => $userEmail,
            'name' => $userName,
            'active_space_id' => $activeSpaceId,
        ],
    ]);
}

if ($uri === '/api/auth/switch-space' && $method === 'POST') {
    $context = requireAuthenticatedUser($database);
    $payload = json_decode((string) file_get_contents('php://input'), true);
    $spaceId = (int) ($payload['space_id'] ?? 0);
    if ($spaceId <= 0) {
        jsonResponse(['message' => 'space_id inválido.'], 422);
    }

    $check = $database->prepare("SELECT COUNT(*) FROM space_members WHERE space_id = :space_id AND user_email = :email AND status IN ('active', 'invited')");
    $check->execute(['space_id' => $spaceId, 'email' => $context['email']]);
    if ((int) $check->fetchColumn() === 0) {
        jsonResponse(['message' => 'Você não participa deste espaço.'], 403);
    }

    $update = $database->prepare('UPDATE auth_tokens SET active_space_id = :space_id WHERE token = :token');
    $update->execute(['space_id' => $spaceId, 'token' => $context['token']]);

    jsonResponse(['message' => 'Espaço ativo atualizado com sucesso.', 'data' => ['active_space_id' => $spaceId]]);
}

if ($uri === '/api/profile/theme' && $method === 'GET') {
    $query = $database->prepare('SELECT theme_preference FROM users WHERE email = :email LIMIT 1');
    $query->execute(['email' => $userEmail]);
    $theme = (string) ($query->fetchColumn() ?: 'light');

    jsonResponse(['data' => ['theme' => $theme]]);
}

if ($uri === '/api/spaces' && $method === 'GET') {
    $query = $database->prepare(
        "SELECT s.id, s.name, s.owner_email, m.role, m.status AS membership_status
         FROM spaces s
         INNER JOIN space_members m ON m.space_id = s.id
         WHERE m.user_email = :email
         ORDER BY s.id DESC"
    );
    $query->execute(['email' => $userEmail]);

    jsonResponse(['data' => $query->fetchAll() ?: []]);
}

if ($uri === '/api/spaces' && $method === 'POST') {
    $payload = json_decode((string) file_get_contents('php://input'), true);
    $name = trim((string) ($payload['name'] ?? ''));
    if ($name === '') {
        jsonResponse(['message' => 'Nome do espaço é obrigatório.'], 422);
    }

    $insert = $database->prepare('INSERT INTO spaces (name, owner_email, created_at) VALUES (:name, :owner_email, :created_at) RETURNING id');
    $insert->execute([
        'name' => $name,
        'owner_email' => $userEmail,
        'created_at' => gmdate('c'),
    ]);
    $spaceId = (int) $insert->fetchColumn();

    $memberInsert = $database->prepare('INSERT INTO space_members (space_id, user_email, role, status, created_at) VALUES (:space_id, :user_email, :role, :status, :created_at)');
    $memberInsert->execute([
        'space_id' => $spaceId,
        'user_email' => $userEmail,
        'role' => 'owner',
        'status' => 'active',
        'created_at' => gmdate('c'),
    ]);

    if ($incomingToken !== '') {
        $update = $database->prepare('UPDATE auth_tokens SET active_space_id = :space_id WHERE token = :token');
        $update->execute(['space_id' => $spaceId, 'token' => $incomingToken]);
    }

    jsonResponse([
        'message' => 'Espaço criado com sucesso.',
        'data' => [
            'id' => $spaceId,
            'name' => $name,
            'owner_email' => $userEmail,
            'role' => 'owner',
            'membership_status' => 'active',
        ],
    ], 201);
}

if (preg_match('#^/api/spaces/(\d+)/invite$#', $uri, $matches) === 1 && $method === 'POST') {
    $spaceId = (int) $matches[1];
    $payload = json_decode((string) file_get_contents('php://input'), true);
    $inviteEmail = trim((string) ($payload['email'] ?? ''));
    $role = trim((string) ($payload['role'] ?? 'member'));

    if ($inviteEmail === '') {
        jsonResponse(['message' => 'E-mail do convite é obrigatório.'], 422);
    }

    $ownerCheck = $database->prepare("SELECT COUNT(*) FROM space_members WHERE space_id = :space_id AND user_email = :email AND role = 'owner'");
    $ownerCheck->execute(['space_id' => $spaceId, 'email' => $userEmail]);
    if ((int) $ownerCheck->fetchColumn() === 0) {
        jsonResponse(['message' => 'Somente o dono pode convidar pessoas.'], 403);
    }

    $inviteInsert = $database->prepare("INSERT INTO space_members (space_id, user_email, role, status, created_at)
        VALUES (:space_id, :user_email, :role, :status, :created_at)
        ON CONFLICT (space_id, user_email)
        DO UPDATE SET role = EXCLUDED.role, status = EXCLUDED.status, created_at = EXCLUDED.created_at");
    $inviteInsert->execute([
        'space_id' => $spaceId,
        'user_email' => $inviteEmail,
        'role' => $role !== '' ? $role : 'member',
        'status' => 'invited',
        'created_at' => gmdate('c'),
    ]);

    jsonResponse(['message' => 'Convite enviado com sucesso.']);
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
         WHERE v.space_id = :space_id
         GROUP BY v.id
         ORDER BY v.id DESC"
    );
    $query->execute(['space_id' => $activeSpaceId]);
    $vaults = $query->fetchAll() ?: [];

    jsonResponse(['data' => $vaults]);
}

if ($uri === '/api/vaults/insights' && $method === 'GET') {
    $totalsQuery = $database->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN t.type = 'deposit' THEN t.amount WHEN t.type = 'withdraw' THEN -t.amount ELSE 0 END), 0) AS total_balance,
            COALESCE(SUM(v.target_amount), 0) AS total_target
         FROM vaults v
         LEFT JOIN vault_transactions t ON t.vault_id = v.id
         WHERE v.space_id = :space_id"
    );
    $totalsQuery->execute(['space_id' => $activeSpaceId]);
    $totals = $totalsQuery->fetch() ?: ['total_balance' => 0, 'total_target' => 0];

    $monthStart = gmdate('Y-m-01T00:00:00+00:00');
    $flowQuery = $database->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN t.type = 'deposit' THEN t.amount ELSE 0 END), 0) AS deposits,
            COALESCE(SUM(CASE WHEN t.type = 'withdraw' THEN t.amount ELSE 0 END), 0) AS withdrawals
         FROM vault_transactions t
         INNER JOIN vaults v ON v.id = t.vault_id
         WHERE v.space_id = :space_id AND t.created_at >= :month_start"
    );
    $flowQuery->execute(['space_id' => $activeSpaceId, 'month_start' => $monthStart]);
    $flow = $flowQuery->fetch() ?: ['deposits' => 0, 'withdrawals' => 0];

    $target = (float) $totals['total_target'];
    $balance = (float) $totals['total_balance'];
    $progress = $target > 0 ? min(100, ($balance / $target) * 100) : 0;

    jsonResponse([
        'data' => [
            'total_balance' => round($balance, 2),
            'total_target' => round($target, 2),
            'progress_percent' => round($progress, 2),
            'monthly_deposits' => round((float) $flow['deposits'], 2),
            'monthly_withdrawals' => round((float) $flow['withdrawals'], 2),
            'monthly_net' => round((float) $flow['deposits'] - (float) $flow['withdrawals'], 2),
        ],
    ]);
}

if ($uri === '/api/transactions/recent' && $method === 'GET') {
    $limit = (int) ($_GET['limit'] ?? 10);
    if ($limit < 1 || $limit > 50) {
        jsonResponse(['message' => 'limit deve estar entre 1 e 50.'], 422);
    }

    $query = $database->prepare(
        "SELECT t.id, t.vault_id, v.name AS vault_name, t.type, t.amount, t.category, t.description, t.created_at
         FROM vault_transactions t
         INNER JOIN vaults v ON v.id = t.vault_id
         WHERE v.space_id = :space_id
         ORDER BY t.id DESC
         LIMIT :limit"
    );
    $query->bindValue(':space_id', $activeSpaceId, PDO::PARAM_INT);
    $query->bindValue(':limit', $limit, PDO::PARAM_INT);
    $query->execute();

    jsonResponse(['data' => $query->fetchAll() ?: []]);
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
    $insert = $database->prepare('INSERT INTO vaults (user_email, space_id, name, target_amount, created_at) VALUES (:email, :space_id, :name, :target, :created_at) RETURNING id');
    $insert->execute([
        'email' => $userEmail,
        'space_id' => $activeSpaceId,
        'name' => $name,
        'target' => $targetAmount,
        'created_at' => $createdAt,
    ]);
    $vaultId = (int) $insert->fetchColumn();

    jsonResponse([
        'message' => 'Cofre criado com sucesso.',
        'data' => [
            'id' => $vaultId,
            'name' => $name,
            'target_amount' => $targetAmount,
            'balance' => 0,
            'created_at' => $createdAt,
        ],
    ], 201);
}

if (preg_match('#^/api/vaults/(\d+)/transactions$#', $uri, $matches) === 1) {
    $vaultId = (int) $matches[1];
    $vaultQuery = $database->prepare('SELECT id, name, target_amount, created_at FROM vaults WHERE id = :id AND space_id = :space_id LIMIT 1');
    $vaultQuery->execute(['id' => $vaultId, 'space_id' => $activeSpaceId]);
    $vault = $vaultQuery->fetch();

    if (!$vault) {
        jsonResponse(['message' => 'Cofre não encontrado.'], 404);
    }

    if ($method === 'GET') {
        $transactionsQuery = $database->prepare('SELECT id, type, amount, category, description, created_at FROM vault_transactions WHERE vault_id = :vault_id ORDER BY id DESC');
        $transactionsQuery->execute(['vault_id' => $vaultId]);
        $transactions = $transactionsQuery->fetchAll() ?: [];

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
        $category = trim((string) ($payload['category'] ?? 'geral'));
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

        if ($category === '') {
            $category = 'geral';
        }

        $createdAt = gmdate('c');
        $insert = $database->prepare('INSERT INTO vault_transactions (vault_id, type, amount, category, description, created_at) VALUES (:vault_id, :type, :amount, :category, :description, :created_at) RETURNING id');
        $insert->execute([
            'vault_id' => $vaultId,
            'type' => $type,
            'amount' => $amount,
            'category' => $category,
            'description' => $description,
            'created_at' => $createdAt,
        ]);
        $transactionId = (int) $insert->fetchColumn();

        $newBalance = $type === 'deposit' ? $currentBalance + $amount : $currentBalance - $amount;
        jsonResponse([
            'message' => 'Movimentação registrada com sucesso.',
            'data' => [
                'id' => $transactionId,
                'vault_id' => $vaultId,
                'type' => $type,
                'amount' => $amount,
                'category' => $category,
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

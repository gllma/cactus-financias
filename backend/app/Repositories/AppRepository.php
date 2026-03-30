<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class AppRepository
{
    public function __construct(private readonly PDO $database)
    {
    }


    /**
     * @template T
     * @param callable():T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        $this->database->beginTransaction();

        try {
            $result = $callback();
            $this->database->commit();

            return $result;
        } catch (\Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $exception;
        }
    }

    public function bootstrapSchema(): void
    {
        $this->database->exec("CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            email VARCHAR(190) NOT NULL UNIQUE,
            name VARCHAR(190) NOT NULL,
            theme_preference VARCHAR(20) NOT NULL DEFAULT 'light'
        )");

        $this->database->exec("CREATE TABLE IF NOT EXISTS spaces (
            id SERIAL PRIMARY KEY,
            name VARCHAR(190) NOT NULL,
            owner_email VARCHAR(190) NOT NULL,
            created_at TIMESTAMPTZ NOT NULL
        )");

        $this->database->exec("CREATE TABLE IF NOT EXISTS space_members (
            id SERIAL PRIMARY KEY,
            space_id INTEGER NOT NULL,
            user_email VARCHAR(190) NOT NULL,
            role VARCHAR(50) NOT NULL DEFAULT 'member',
            status VARCHAR(50) NOT NULL DEFAULT 'active',
            created_at TIMESTAMPTZ NOT NULL,
            UNIQUE (space_id, user_email)
        )");

        $this->database->exec("CREATE TABLE IF NOT EXISTS auth_tokens (
            id SERIAL PRIMARY KEY,
            token VARCHAR(255) NOT NULL UNIQUE,
            user_email VARCHAR(190) NOT NULL,
            active_space_id INTEGER,
            created_at TIMESTAMPTZ NOT NULL
        )");

        $this->database->exec("CREATE TABLE IF NOT EXISTS vaults (
            id SERIAL PRIMARY KEY,
            user_email VARCHAR(190) NOT NULL,
            space_id INTEGER,
            name VARCHAR(190) NOT NULL,
            target_amount NUMERIC(14,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMPTZ NOT NULL
        )");

        $this->database->exec("CREATE TABLE IF NOT EXISTS vault_transactions (
            id SERIAL PRIMARY KEY,
            vault_id INTEGER NOT NULL,
            type VARCHAR(20) NOT NULL,
            amount NUMERIC(14,2) NOT NULL,
            category VARCHAR(80) NOT NULL DEFAULT 'geral',
            description TEXT,
            created_at TIMESTAMPTZ NOT NULL
        )");
    }

    public function upsertUser(string $email, string $name, string $theme = 'light'): void
    {
        $insert = $this->database->prepare("INSERT INTO users (email, name, theme_preference)
            VALUES (:email, :name, :theme)
            ON CONFLICT (email) DO NOTHING");
        $insert->execute(['email' => $email, 'name' => $name, 'theme' => $theme]);
    }

    public function updateUserName(string $email, string $name): void
    {
        $update = $this->database->prepare('UPDATE users SET name = :name WHERE email = :email');
        $update->execute(['name' => $name, 'email' => $email]);
    }

    public function findUserNameByEmail(string $email): ?string
    {
        $query = $this->database->prepare('SELECT name FROM users WHERE email = :email LIMIT 1');
        $query->execute(['email' => $email]);
        $name = $query->fetchColumn();

        return $name !== false ? (string) $name : null;
    }

    public function findUserIdByEmail(string $email): ?int
    {
        $query = $this->database->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $query->execute(['email' => $email]);
        $id = $query->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    public function findThemeByEmail(string $email): string
    {
        $query = $this->database->prepare('SELECT theme_preference FROM users WHERE email = :email LIMIT 1');
        $query->execute(['email' => $email]);

        return (string) ($query->fetchColumn() ?: 'light');
    }

    public function updateTheme(string $email, string $theme): void
    {
        $update = $this->database->prepare('UPDATE users SET theme_preference = :theme WHERE email = :email');
        $update->execute(['theme' => $theme, 'email' => $email]);
    }

    public function findSpaceByOwner(string $email): ?int
    {
        $query = $this->database->prepare('SELECT id FROM spaces WHERE owner_email = :email LIMIT 1');
        $query->execute(['email' => $email]);
        $id = $query->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    public function createSpace(string $name, string $ownerEmail): int
    {
        $insert = $this->database->prepare('INSERT INTO spaces (name, owner_email, created_at) VALUES (:name, :owner_email, :created_at) RETURNING id');
        $insert->execute([
            'name' => $name,
            'owner_email' => $ownerEmail,
            'created_at' => gmdate('c'),
        ]);

        return (int) $insert->fetchColumn();
    }

    public function upsertMember(int $spaceId, string $email, string $role, string $status): void
    {
        $insert = $this->database->prepare("INSERT INTO space_members (space_id, user_email, role, status, created_at)
            VALUES (:space_id, :user_email, :role, :status, :created_at)
            ON CONFLICT (space_id, user_email)
            DO UPDATE SET role = EXCLUDED.role, status = EXCLUDED.status, created_at = EXCLUDED.created_at");
        $insert->execute([
            'space_id' => $spaceId,
            'user_email' => $email,
            'role' => $role,
            'status' => $status,
            'created_at' => gmdate('c'),
        ]);
    }

    public function isMember(int $spaceId, string $email): bool
    {
        $query = $this->database->prepare("SELECT COUNT(*) FROM space_members WHERE space_id = :space_id AND user_email = :email AND status IN ('active', 'invited')");
        $query->execute(['space_id' => $spaceId, 'email' => $email]);

        return (int) $query->fetchColumn() > 0;
    }

    public function isSpaceOwner(int $spaceId, string $email): bool
    {
        $query = $this->database->prepare("SELECT COUNT(*) FROM space_members WHERE space_id = :space_id AND user_email = :email AND role = 'owner'");
        $query->execute(['space_id' => $spaceId, 'email' => $email]);

        return (int) $query->fetchColumn() > 0;
    }

    public function listSpaces(string $email): array
    {
        $query = $this->database->prepare(
            "SELECT s.id, s.name, s.owner_email, m.role, m.status AS membership_status
             FROM spaces s
             INNER JOIN space_members m ON m.space_id = s.id
             WHERE m.user_email = :email
             ORDER BY s.id DESC"
        );
        $query->execute(['email' => $email]);

        return $query->fetchAll() ?: [];
    }

    public function createToken(string $email, int $spaceId): string
    {
        $token = bin2hex(random_bytes(24));
        $insert = $this->database->prepare('INSERT INTO auth_tokens (token, user_email, active_space_id, created_at) VALUES (:token, :email, :active_space_id, :created_at)');
        $insert->execute([
            'token' => $token,
            'email' => $email,
            'active_space_id' => $spaceId,
            'created_at' => gmdate('c'),
        ]);

        return $token;
    }

    public function findTokenContext(string $token): ?array
    {
        $query = $this->database->prepare('SELECT token, user_email, active_space_id FROM auth_tokens WHERE token = :token LIMIT 1');
        $query->execute(['token' => $token]);
        $row = $query->fetch();

        if (!$row) {
            return null;
        }

        return [
            'token' => (string) $row['token'],
            'email' => (string) $row['user_email'],
            'active_space_id' => $row['active_space_id'] !== null ? (int) $row['active_space_id'] : null,
        ];
    }

    public function updateTokenSpace(string $token, int $spaceId): void
    {
        $update = $this->database->prepare('UPDATE auth_tokens SET active_space_id = :space_id WHERE token = :token');
        $update->execute(['space_id' => $spaceId, 'token' => $token]);
    }

    public function backfillVaultSpace(string $email, int $spaceId): void
    {
        $update = $this->database->prepare('UPDATE vaults SET space_id = :space_id WHERE user_email = :email AND (space_id IS NULL OR space_id = 0)');
        $update->execute(['space_id' => $spaceId, 'email' => $email]);
    }

    public function createVault(string $email, int $spaceId, string $name, float $targetAmount): array
    {
        $createdAt = gmdate('c');
        $insert = $this->database->prepare('INSERT INTO vaults (user_email, space_id, name, target_amount, created_at) VALUES (:email, :space_id, :name, :target, :created_at) RETURNING id');
        $insert->execute([
            'email' => $email,
            'space_id' => $spaceId,
            'name' => $name,
            'target' => $targetAmount,
            'created_at' => $createdAt,
        ]);

        return [
            'id' => (int) $insert->fetchColumn(),
            'name' => $name,
            'target_amount' => $targetAmount,
            'balance' => 0,
            'created_at' => $createdAt,
        ];
    }

    public function listVaults(int $spaceId): array
    {
        $query = $this->database->prepare(
            "SELECT v.id, v.name, v.target_amount, v.created_at,
                    COALESCE(SUM(CASE WHEN t.type = 'deposit' THEN t.amount WHEN t.type = 'withdraw' THEN -t.amount ELSE 0 END), 0) AS balance
             FROM vaults v
             LEFT JOIN vault_transactions t ON t.vault_id = v.id
             WHERE v.space_id = :space_id
             GROUP BY v.id
             ORDER BY v.id DESC"
        );
        $query->execute(['space_id' => $spaceId]);

        return $query->fetchAll() ?: [];
    }

    public function findVault(int $vaultId, int $spaceId): ?array
    {
        $query = $this->database->prepare('SELECT id, name, target_amount, created_at FROM vaults WHERE id = :id AND space_id = :space_id LIMIT 1');
        $query->execute(['id' => $vaultId, 'space_id' => $spaceId]);
        $vault = $query->fetch();

        return $vault ?: null;
    }

    public function listVaultTransactions(int $vaultId): array
    {
        $query = $this->database->prepare('SELECT id, type, amount, category, description, created_at FROM vault_transactions WHERE vault_id = :vault_id ORDER BY id DESC');
        $query->execute(['vault_id' => $vaultId]);

        return $query->fetchAll() ?: [];
    }

    public function vaultBalance(int $vaultId): float
    {
        $query = $this->database->prepare("SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount WHEN type = 'withdraw' THEN -amount ELSE 0 END), 0) FROM vault_transactions WHERE vault_id = :vault_id");
        $query->execute(['vault_id' => $vaultId]);

        return (float) $query->fetchColumn();
    }

    public function createTransaction(int $vaultId, string $type, float $amount, string $category, string $description): array
    {
        $createdAt = gmdate('c');
        $insert = $this->database->prepare('INSERT INTO vault_transactions (vault_id, type, amount, category, description, created_at) VALUES (:vault_id, :type, :amount, :category, :description, :created_at) RETURNING id');
        $insert->execute([
            'vault_id' => $vaultId,
            'type' => $type,
            'amount' => $amount,
            'category' => $category,
            'description' => $description,
            'created_at' => $createdAt,
        ]);

        return [
            'id' => (int) $insert->fetchColumn(),
            'vault_id' => $vaultId,
            'type' => $type,
            'amount' => $amount,
            'category' => $category,
            'description' => $description,
            'created_at' => $createdAt,
        ];
    }

    public function vaultInsights(int $spaceId): array
    {
        $totalsQuery = $this->database->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN t.type = 'deposit' THEN t.amount WHEN t.type = 'withdraw' THEN -t.amount ELSE 0 END), 0) AS total_balance,
                COALESCE(SUM(v.target_amount), 0) AS total_target
             FROM vaults v
             LEFT JOIN vault_transactions t ON t.vault_id = v.id
             WHERE v.space_id = :space_id"
        );
        $totalsQuery->execute(['space_id' => $spaceId]);
        $totals = $totalsQuery->fetch() ?: ['total_balance' => 0, 'total_target' => 0];

        $monthStart = gmdate('Y-m-01T00:00:00+00:00');
        $flowQuery = $this->database->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN t.type = 'deposit' THEN t.amount ELSE 0 END), 0) AS deposits,
                COALESCE(SUM(CASE WHEN t.type = 'withdraw' THEN t.amount ELSE 0 END), 0) AS withdrawals
             FROM vault_transactions t
             INNER JOIN vaults v ON v.id = t.vault_id
             WHERE v.space_id = :space_id AND t.created_at >= :month_start"
        );
        $flowQuery->execute(['space_id' => $spaceId, 'month_start' => $monthStart]);
        $flow = $flowQuery->fetch() ?: ['deposits' => 0, 'withdrawals' => 0];

        return [
            'total_balance' => (float) $totals['total_balance'],
            'total_target' => (float) $totals['total_target'],
            'monthly_deposits' => (float) $flow['deposits'],
            'monthly_withdrawals' => (float) $flow['withdrawals'],
        ];
    }

    public function recentTransactions(int $spaceId, int $limit): array
    {
        $query = $this->database->prepare(
            "SELECT t.id, t.vault_id, v.name AS vault_name, t.type, t.amount, t.category, t.description, t.created_at
             FROM vault_transactions t
             INNER JOIN vaults v ON v.id = t.vault_id
             WHERE v.space_id = :space_id
             ORDER BY t.id DESC
             LIMIT :limit"
        );
        $query->bindValue(':space_id', $spaceId, PDO::PARAM_INT);
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll() ?: [];
    }

    public function totalUsers(): int
    {
        return (int) $this->database->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function totalDarkThemeUsers(): int
    {
        return (int) $this->database->query("SELECT COUNT(*) FROM users WHERE theme_preference = 'dark'")->fetchColumn();
    }
}

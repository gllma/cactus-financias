<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\CreateSpaceAction;
use App\Actions\EnsurePersonalSpaceAction;
use App\Actions\LoginUserAction;
use App\Repositories\AppRepository;
use RuntimeException;

class AppService
{
    public function __construct(
        private readonly AppRepository $repository,
        private readonly EnsurePersonalSpaceAction $ensurePersonalSpaceAction,
        private readonly LoginUserAction $loginUserAction,
        private readonly CreateSpaceAction $createSpaceAction,
    ) {
        $this->repository->bootstrapSchema();
    }


    public function register(string $email, string $name, string $password): array
    {
        if ($email === '' || $name === '' || $password === '') {
            throw new RuntimeException('Nome, e-mail e senha são obrigatórios.');
        }

        if ($this->repository->userExistsByEmail($email)) {
            throw new RuntimeException('E-mail já cadastrado.');
        }

        return $this->loginUserAction->execute($email, $name);
    }

    public function login(string $email, string $name, string $password): array
    {
        if ($email === '' || $password === '') {
            throw new RuntimeException('E-mail e senha são obrigatórios.');
        }

        return $this->loginUserAction->execute($email, $name);
    }

    public function contextFromToken(string $token): ?array
    {
        return $this->repository->findTokenContext($token);
    }

    public function resolveContext(string $token, string $headerEmail, string $headerName, ?int $headerSpaceId): array
    {
        $context = $token !== '' ? $this->repository->findTokenContext($token) : null;

        if ($token !== '' && $context === null) {
            throw new RuntimeException('Token inválido.');
        }

        $email = $context['email'] ?? ($headerEmail !== '' ? $headerEmail : 'admin@cactus.com');
        $name = $this->repository->findUserNameByEmail($email) ?? ($headerName !== '' ? $headerName : 'Maria Silva');

        $this->repository->upsertUser($email, $name);
        $personalSpaceId = $this->ensurePersonalSpaceAction->execute($email);
        $this->repository->backfillVaultSpace($email, $personalSpaceId);

        $activeSpaceId = $headerSpaceId ?? ($context['active_space_id'] ?? $personalSpaceId);
        if (!$this->repository->isMember($activeSpaceId, $email)) {
            $activeSpaceId = $personalSpaceId;
        }

        if ($token !== '' && (int) ($context['active_space_id'] ?? 0) !== $activeSpaceId) {
            $this->repository->updateTokenSpace($token, $activeSpaceId);
        }

        return [
            'token' => $token,
            'email' => $email,
            'name' => $name,
            'active_space_id' => $activeSpaceId,
        ];
    }

    public function switchSpace(string $token, int $spaceId): void
    {
        $context = $this->repository->findTokenContext($token);
        if ($context === null) {
            throw new RuntimeException('Token inválido.');
        }

        if (!$this->repository->isMember($spaceId, $context['email'])) {
            throw new RuntimeException('Você não participa deste espaço.');
        }

        $this->repository->updateTokenSpace($token, $spaceId);
    }

    public function theme(string $email): string
    {
        return $this->repository->findThemeByEmail($email);
    }

    public function updateTheme(string $email, string $theme): void
    {
        if (!in_array($theme, ['light', 'dark'], true)) {
            throw new RuntimeException('Tema inválido.');
        }

        $this->repository->updateTheme($email, $theme);
    }

    public function listSpaces(string $email): array
    {
        return $this->repository->listSpaces($email);
    }

    public function createSpace(string $email, string $name, string $token = ''): array
    {
        if ($name === '') {
            throw new RuntimeException('Nome do espaço é obrigatório.');
        }

        return $this->createSpaceAction->execute($email, $name, $token);
    }

    public function inviteToSpace(int $spaceId, string $ownerEmail, string $inviteEmail, string $role): void
    {
        if ($inviteEmail === '') {
            throw new RuntimeException('E-mail do convite é obrigatório.');
        }

        if (!$this->repository->isSpaceOwner($spaceId, $ownerEmail)) {
            throw new RuntimeException('Somente o dono pode convidar pessoas.');
        }

        $this->repository->upsertMember($spaceId, $inviteEmail, $role !== '' ? $role : 'member', 'invited');
    }

    public function observabilitySummary(string $email, int $periodMinutes): array
    {
        if ($periodMinutes < 5 || $periodMinutes > 1440) {
            throw new RuntimeException('period_minutes deve estar entre 5 e 1440.');
        }

        $allowlistRaw = getenv('OBSERVABILITY_ALLOWLIST') ?: 'admin@cactus.com,1';
        $allowlist = array_filter(array_map('trim', explode(',', $allowlistRaw)));
        $userId = (string) ($this->repository->findUserIdByEmail($email) ?? '');

        if (!in_array($email, $allowlist, true) && !in_array($userId, $allowlist, true)) {
            throw new RuntimeException('Acesso ao painel de observabilidade não autorizado.');
        }

        return [
            'failed_jobs' => 2,
            'pending_jobs' => 5,
            'recent_exceptions' => 1,
            'total_users' => $this->repository->totalUsers(),
            'dark_theme_users' => $this->repository->totalDarkThemeUsers(),
            'simulated_uptime_percent' => 99.95,
            'period_minutes' => $periodMinutes,
        ];
    }

    public function listVaults(int $spaceId): array
    {
        return $this->repository->listVaults($spaceId);
    }

    public function createVault(string $email, int $spaceId, string $name, float $targetAmount): array
    {
        if ($name === '') {
            throw new RuntimeException('O nome do cofre é obrigatório.');
        }

        if ($targetAmount < 0) {
            throw new RuntimeException('target_amount não pode ser negativo.');
        }

        return $this->repository->createVault($email, $spaceId, $name, $targetAmount);
    }

    public function vaultDetail(int $spaceId, int $vaultId): array
    {
        $vault = $this->repository->findVault($vaultId, $spaceId);
        if ($vault === null) {
            throw new RuntimeException('Cofre não encontrado.');
        }

        return [
            'vault' => [
                'id' => (int) $vault['id'],
                'name' => $vault['name'],
                'target_amount' => (float) $vault['target_amount'],
                'balance' => $this->repository->vaultBalance($vaultId),
                'created_at' => $vault['created_at'],
            ],
            'transactions' => $this->repository->listVaultTransactions($vaultId),
        ];
    }

    public function addVaultTransaction(int $spaceId, int $vaultId, string $type, float $amount, string $category, string $description): array
    {
        $vault = $this->repository->findVault($vaultId, $spaceId);
        if ($vault === null) {
            throw new RuntimeException('Cofre não encontrado.');
        }

        if (!in_array($type, ['deposit', 'withdraw'], true)) {
            throw new RuntimeException('Tipo de movimentação inválido.');
        }

        if ($amount <= 0) {
            throw new RuntimeException('amount deve ser maior que zero.');
        }

        $currentBalance = $this->repository->vaultBalance($vaultId);
        if ($type === 'withdraw' && $amount > $currentBalance) {
            throw new RuntimeException('Saldo insuficiente para saque.');
        }

        $transaction = $this->repository->createTransaction(
            $vaultId,
            $type,
            $amount,
            $category !== '' ? $category : 'geral',
            $description,
        );

        $transaction['balance'] = $type === 'deposit' ? $currentBalance + $amount : $currentBalance - $amount;

        return $transaction;
    }

    public function vaultInsights(int $spaceId): array
    {
        $insights = $this->repository->vaultInsights($spaceId);

        $target = $insights['total_target'];
        $balance = $insights['total_balance'];
        $progress = $target > 0 ? min(100, ($balance / $target) * 100) : 0;

        return [
            'total_balance' => round($balance, 2),
            'total_target' => round($target, 2),
            'progress_percent' => round($progress, 2),
            'monthly_deposits' => round($insights['monthly_deposits'], 2),
            'monthly_withdrawals' => round($insights['monthly_withdrawals'], 2),
            'monthly_net' => round($insights['monthly_deposits'] - $insights['monthly_withdrawals'], 2),
        ];
    }

    public function recentTransactions(int $spaceId, int $limit): array
    {
        if ($limit < 1 || $limit > 50) {
            throw new RuntimeException('limit deve estar entre 1 e 50.');
        }

        return $this->repository->recentTransactions($spaceId, $limit);
    }
}

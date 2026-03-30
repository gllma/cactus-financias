<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\AppRepository;

class LoginUserAction
{
    public function __construct(
        private readonly AppRepository $repository,
        private readonly EnsurePersonalSpaceAction $ensurePersonalSpaceAction,
    ) {
    }

    public function execute(string $email, string $name): array
    {
        return $this->repository->transaction(function () use ($email, $name): array {
            $this->repository->upsertUser($email, $name, 'light');
            $this->repository->updateUserName($email, $name);

            $spaceId = $this->ensurePersonalSpaceAction->execute($email);
            $token = $this->repository->createToken($email, $spaceId);

            return [
                'token' => $token,
                'email' => $email,
                'name' => $name,
                'active_space_id' => $spaceId,
            ];
        });
    }
}

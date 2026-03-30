<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\AppRepository;

class CreateSpaceAction
{
    public function __construct(private readonly AppRepository $repository)
    {
    }

    public function execute(string $email, string $name, string $token = ''): array
    {
        return $this->repository->transaction(function () use ($email, $name, $token): array {
            $spaceId = $this->repository->createSpace($name, $email);
            $this->repository->upsertMember($spaceId, $email, 'owner', 'active');

            if ($token !== '') {
                $this->repository->updateTokenSpace($token, $spaceId);
            }

            return [
                'id' => $spaceId,
                'name' => $name,
                'owner_email' => $email,
                'role' => 'owner',
                'membership_status' => 'active',
            ];
        });
    }
}

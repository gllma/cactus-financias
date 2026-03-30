<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\AppRepository;

class EnsurePersonalSpaceAction
{
    public function __construct(private readonly AppRepository $repository)
    {
    }

    public function execute(string $email): int
    {
        $spaceId = $this->repository->findSpaceByOwner($email);
        if ($spaceId === null) {
            $spaceId = $this->repository->createSpace('Espaço de ' . $email, $email);
        }

        $this->repository->upsertMember($spaceId, $email, 'owner', 'active');

        return $spaceId;
    }
}

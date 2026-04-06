<?php

namespace App\Core\Application\Auth;

use App\Core\Domain\Auth\User;
use App\Core\Domain\Contracts\Auth\AuthenticationSession;
use App\Core\Domain\Contracts\Auth\UserRepository;
use App\Core\Domain\Exceptions\AuthenticationRequiredException;

final readonly class GetAuthenticatedUserHandler
{
    public function __construct(
        private AuthenticationSession $session,
        private UserRepository $users,
    ) {
    }

    public function handle(GetAuthenticatedUser $command): User
    {
        $userId = $this->session->currentUserId();

        if ($userId === null) {
            throw new AuthenticationRequiredException();
        }

        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new AuthenticationRequiredException();
        }

        return $user;
    }
}

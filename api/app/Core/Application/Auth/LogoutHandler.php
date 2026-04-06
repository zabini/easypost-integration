<?php

namespace App\Core\Application\Auth;

use App\Core\Domain\Contracts\Auth\AuthenticationSession;
use App\Core\Domain\Exceptions\AuthenticationRequiredException;

final readonly class LogoutHandler
{
    public function __construct(private AuthenticationSession $session)
    {
    }

    public function handle(Logout $command): void
    {
        if ($this->session->currentUserId() === null) {
            throw new AuthenticationRequiredException();
        }

        $this->session->logout();
    }
}

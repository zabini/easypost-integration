<?php

namespace App\Core\Application\Auth;

use App\Core\Domain\Auth\User;
use App\Core\Domain\Auth\ValueObjects\Email;
use App\Core\Domain\Contracts\Auth\AuthenticationSession;
use App\Core\Domain\Contracts\Auth\PasswordHasher;
use App\Core\Domain\Contracts\Auth\UserRepository;
use App\Core\Domain\Exceptions\InvalidCredentialsException;

final readonly class LoginHandler
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $passwordHasher,
        private AuthenticationSession $session,
    ) {
    }

    public function handle(Login $command): User
    {
        $user = $this->users->findByEmail(
            Email::fromString($command->email),
        );

        if ($user === null || ! $this->passwordHasher->check($command->password, $user->passwordHash())) {
            throw new InvalidCredentialsException();
        }

        $this->session->login($user->id());

        return $user;
    }
}

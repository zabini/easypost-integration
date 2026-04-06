<?php

namespace App\Core\Application\Auth;

use App\Core\Domain\Auth\User;
use App\Core\Domain\Auth\ValueObjects\Email;
use App\Core\Domain\Auth\ValueObjects\PlainPassword;
use App\Core\Domain\Auth\ValueObjects\UserName;
use App\Core\Domain\Contracts\Auth\AuthenticationSession;
use App\Core\Domain\Contracts\Auth\PasswordHasher;
use App\Core\Domain\Contracts\Auth\UserRepository;
use App\Core\Domain\Exceptions\DuplicateEmailException;

final readonly class SignUpHandler
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $passwordHasher,
        private AuthenticationSession $session,
    ) {
    }

    public function handle(SignUp $command): User
    {
        $name = UserName::fromString($command->name);
        $email = Email::fromString($command->email);

        if ($this->users->existsByEmail($email)) {
            throw new DuplicateEmailException();
        }

        $passwordHash = $this->passwordHasher->hash(
            PlainPassword::fromString($command->password),
        );

        $user = $this->users->create($name, $email, $passwordHash);

        $this->session->login($user->id());

        return $user;
    }
}

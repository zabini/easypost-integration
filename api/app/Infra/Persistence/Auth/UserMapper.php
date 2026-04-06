<?php

namespace App\Infra\Persistence\Auth;

use App\Core\Domain\Auth\User as DomainUser;
use App\Core\Domain\Auth\ValueObjects\Email;
use App\Core\Domain\Auth\ValueObjects\UserName;
use App\Models\User as EloquentUser;

final class UserMapper
{
    public function toDomain(EloquentUser $user): DomainUser
    {
        return new DomainUser(
            id: (int) $user->getKey(),
            name: UserName::fromString($user->name),
            email: Email::fromString($user->email),
            passwordHash: $user->password,
        );
    }
}

<?php

namespace App\Infra\Persistence\Auth;

use App\Core\Domain\Auth\User;
use App\Core\Domain\Auth\ValueObjects\Email;
use App\Core\Domain\Auth\ValueObjects\UserName;
use App\Core\Domain\Contracts\Auth\UserRepository;
use App\Models\User as EloquentUser;

final readonly class EloquentUserRepository implements UserRepository
{
    public function __construct(private UserMapper $mapper)
    {
    }

    public function existsByEmail(Email $email): bool
    {
        return EloquentUser::query()
            ->where('email', $email->value())
            ->exists();
    }

    public function create(UserName $name, Email $email, string $passwordHash): User
    {
        $user = EloquentUser::query()->create([
            'name' => $name->value(),
            'email' => $email->value(),
            'password' => $passwordHash,
        ]);

        return $this->mapper->toDomain($user);
    }

    public function findByEmail(Email $email): ?User
    {
        $user = EloquentUser::query()
            ->where('email', $email->value())
            ->first();

        if ($user === null) {
            return null;
        }

        return $this->mapper->toDomain($user);
    }

    public function findById(int $id): ?User
    {
        $user = EloquentUser::query()->find($id);

        if ($user === null) {
            return null;
        }

        return $this->mapper->toDomain($user);
    }
}

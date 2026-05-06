<?php

namespace App\Core\Domain\Exceptions;

final class AuthenticationRequiredException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('Unauthenticated.');
    }

    public function responseStatusCode(): int
    {
        return 401;
    }
}

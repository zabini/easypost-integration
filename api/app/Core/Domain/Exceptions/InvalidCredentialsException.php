<?php

namespace App\Core\Domain\Exceptions;

final class InvalidCredentialsException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('Invalid credentials.');
    }

    public function responseStatusCode(): int
    {
        return 401;
    }
}

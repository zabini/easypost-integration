<?php

namespace App\Core\Domain\Exceptions;

use RuntimeException;

final class AuthenticationRequiredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Unauthenticated.');
    }
}

<?php

namespace App\Core\Domain\Exceptions;

use RuntimeException;

final class DuplicateEmailException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This email is already registered.');
    }
}

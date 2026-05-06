<?php

namespace App\Core\Domain\Exceptions;

final class DuplicateEmailException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('This email is already registered.');
    }

    public function responseStatusCode(): int
    {
        return 422;
    }

    /**
     * @return array<string, mixed>
     */
    public function responseBody(): array
    {
        return [
            'message' => 'The given data was invalid.',
            'errors' => [
                'email' => [$this->getMessage()],
            ],
        ];
    }
}

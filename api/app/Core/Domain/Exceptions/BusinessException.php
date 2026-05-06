<?php

namespace App\Core\Domain\Exceptions;

use App\Core\Domain\Contracts\Exceptions\ShouldRender;
use RuntimeException;

class BusinessException extends RuntimeException implements ShouldRender
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }

    public function responseStatusCode(): int
    {
        return 400;
    }

    /**
     * @return array<string, mixed>
     */
    public function responseBody(): array
    {
        return [
            'message' => $this->getMessage(),
        ];
    }
}

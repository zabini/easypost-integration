<?php

namespace App\Core\Domain\Contracts\Exceptions;

interface ShouldRender
{
    public function responseStatusCode(): int;

    /**
     * @return array<string, mixed>
     */
    public function responseBody(): array;
}

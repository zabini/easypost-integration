<?php

namespace App\Infra\Http\Controller\Auth;

use App\Core\Application\Auth\GetAuthenticatedUser;
use App\Core\Application\Auth\GetAuthenticatedUserHandler;
use Illuminate\Http\JsonResponse;

final class GetAuthenticatedUserController
{
    public function __invoke(GetAuthenticatedUserHandler $handler): JsonResponse
    {
        $user = $handler->handle(new GetAuthenticatedUser());

        return response()->json([
            'data' => $user->toPublicArray(),
        ]);
    }
}

<?php

namespace App\Infra\Http\Controller\Auth;

use App\Core\Application\Auth\LoginHandler;
use App\Infra\Http\Request\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;

final class LoginController
{
    public function __invoke(LoginRequest $request, LoginHandler $handler): JsonResponse
    {
        $session = $handler->handle($request->toCommand());

        return response()->json([
            'data' => $session->user->toPublicArray(),
            'meta' => [
                'access_token' => $session->accessToken,
                'token_type' => 'Bearer',
            ],
        ]);
    }
}

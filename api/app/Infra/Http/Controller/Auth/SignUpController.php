<?php

namespace App\Infra\Http\Controller\Auth;

use App\Core\Application\Auth\SignUpHandler;
use App\Infra\Http\Request\Auth\SignUpRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class SignUpController
{
    public function __invoke(SignUpRequest $request, SignUpHandler $handler): JsonResponse
    {
        $session = $handler->handle($request->toCommand());

        return response()->json([
            'data' => $session->user->toPublicArray(),
            'meta' => [
                'access_token' => $session->accessToken,
                'token_type' => 'Bearer',
            ],
        ], Response::HTTP_CREATED);
    }
}

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
        $user = $handler->handle($request->toCommand());

        return response()->json([
            'data' => $user->toPublicArray(),
        ], Response::HTTP_CREATED);
    }
}

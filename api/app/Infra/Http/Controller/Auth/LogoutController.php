<?php

namespace App\Infra\Http\Controller\Auth;

use App\Core\Application\Auth\Logout;
use App\Core\Application\Auth\LogoutHandler;
use Illuminate\Http\JsonResponse;

final class LogoutController
{
    public function __invoke(LogoutHandler $handler): JsonResponse
    {
        $handler->handle(new Logout());

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}

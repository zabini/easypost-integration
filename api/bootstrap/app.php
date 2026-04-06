<?php

use App\Core\Domain\Exceptions\AuthenticationRequiredException;
use App\Core\Domain\Exceptions\DuplicateEmailException;
use App\Core\Domain\Exceptions\InvalidCredentialsException;
use App\Core\Domain\Exceptions\ShippingLabelAddressNotSupportedException;
use App\Core\Domain\Exceptions\ShippingLabelNotFoundException;
use App\Core\Domain\Exceptions\ShippingLabelUspsRateUnavailableException;
use App\Core\Domain\Exceptions\ShippingProviderAuthenticationException;
use App\Core\Domain\Exceptions\ShippingProviderRequestException;
use App\Core\Domain\Exceptions\ShippingProviderUnavailableException;
use App\Core\Domain\Exceptions\ShippingProviderUnexpectedResponseException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (DuplicateEmailException $exception) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'email' => [$exception->getMessage()],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        $exceptions->render(function (InvalidCredentialsException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNAUTHORIZED);
        });

        $exceptions->render(function (AuthenticationRequiredException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNAUTHORIZED);
        });

        $exceptions->render(function (ShippingLabelAddressNotSupportedException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        $exceptions->render(function (ShippingLabelNotFoundException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        });

        $exceptions->render(function (ShippingLabelUspsRateUnavailableException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        $exceptions->render(function (ShippingProviderRequestException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        $exceptions->render(function (ShippingProviderAuthenticationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        });

        $exceptions->render(function (ShippingProviderUnavailableException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        });

        $exceptions->render(function (ShippingProviderUnexpectedResponseException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_GATEWAY);
        });
    })->create();

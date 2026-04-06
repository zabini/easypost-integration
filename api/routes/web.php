<?php

use App\Http\Controllers\IndexController;
use App\Infra\Http\Controller\Auth\GetAuthenticatedUserController;
use App\Infra\Http\Controller\Auth\LoginController;
use App\Infra\Http\Controller\Auth\LogoutController;
use App\Infra\Http\Controller\Auth\SignUpController;
use App\Infra\Http\Controller\ShippingLabel\CreateController;
use Illuminate\Support\Facades\Route;

Route::get('/', IndexController::class);

Route::prefix('auth')->group(function (): void {
    Route::post('/signup', SignUpController::class);
    Route::post('/login', LoginController::class);
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', LogoutController::class);
        Route::get('/me', GetAuthenticatedUserController::class);
    });
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/shipping-labels', CreateController::class);
});

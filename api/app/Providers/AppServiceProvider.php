<?php

namespace App\Providers;

use App\Core\Domain\Contracts\Auth\AuthenticationSession;
use App\Core\Domain\Contracts\Auth\PasswordHasher;
use App\Core\Domain\Contracts\Auth\UserRepository;
use App\Core\Domain\Contracts\ShippingLabel\ShippingLabelRepository;
use App\Infra\Auth\LaravelPasswordHasher;
use App\Infra\Auth\SanctumAuthenticationSession;
use App\Infra\Persistence\Auth\EloquentUserRepository;
use App\Infra\Persistence\Auth\UserMapper;
use App\Infra\Persistence\ShippingLabel\EloquentShippingLabelRepository;
use App\Infra\Persistence\ShippingLabel\ShippingLabelMapper;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(UserMapper::class);
        $this->app->singleton(ShippingLabelMapper::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(ShippingLabelRepository::class, EloquentShippingLabelRepository::class);
        $this->app->bind(PasswordHasher::class, LaravelPasswordHasher::class);
        $this->app->bind(AuthenticationSession::class, SanctumAuthenticationSession::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

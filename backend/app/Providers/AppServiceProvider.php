<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Packages\Auth\Domain\Repository\UserRepositoryInterface;
use Packages\Auth\Infrastructure\Repository\EloquentUserRepository;
use Packages\Family\Domain\Repository\ChildRepositoryInterface;
use Packages\Family\Domain\Repository\FamilyRepositoryInterface;
use Packages\Family\Infrastructure\Repository\EloquentChildRepository;
use Packages\Family\Infrastructure\Repository\EloquentFamilyRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(FamilyRepositoryInterface::class, EloquentFamilyRepository::class);
        $this->app->bind(ChildRepositoryInterface::class, EloquentChildRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

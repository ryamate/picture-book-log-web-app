<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Packages\Auth\Domain\Repository\UserRepositoryInterface;
use Packages\Auth\Infrastructure\Repository\EloquentUserRepository;
use Packages\Family\Domain\Repository\ChildRepositoryInterface;
use Packages\Family\Domain\Repository\FamilyRepositoryInterface;
use Packages\Bookshelf\Domain\Repository\PictureBookRepositoryInterface;
use Packages\Bookshelf\Infrastructure\Repository\EloquentPictureBookRepository;
use Packages\Family\Infrastructure\Repository\EloquentChildRepository;
use Packages\Family\Infrastructure\Repository\EloquentFamilyRepository;
use Packages\ReadLog\Application\Validator\FamilyOwnershipValidatorInterface;
use Packages\ReadLog\Domain\Repository\ReadRecordRepositoryInterface;
use Packages\ReadLog\Domain\Repository\TagRepositoryInterface;
use Packages\ReadLog\Infrastructure\Repository\EloquentReadRecordRepository;
use Packages\ReadLog\Infrastructure\Repository\EloquentTagRepository;
use Packages\ReadLog\Infrastructure\Validator\EloquentFamilyOwnershipValidator;

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
        $this->app->bind(PictureBookRepositoryInterface::class, EloquentPictureBookRepository::class);
        $this->app->bind(ReadRecordRepositoryInterface::class, EloquentReadRecordRepository::class);
        $this->app->bind(TagRepositoryInterface::class, EloquentTagRepository::class);
        $this->app->bind(FamilyOwnershipValidatorInterface::class, EloquentFamilyOwnershipValidator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

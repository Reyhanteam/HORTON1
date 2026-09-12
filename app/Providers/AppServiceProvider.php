<?php

namespace App\Providers;

use App\Contracts\AdminAuthenticator;
use App\Contracts\FeatureManager;
use App\Contracts\SettingsStore;
use App\Contracts\UserAccessChecker;
use App\Contracts\UserLifecycle;
use App\Services\Auth\LaravelAdminAuthenticator;
use App\Services\Auth\UserAccessService;
use App\Services\Auth\UserLifecycleService;
use App\Services\Settings\DatabaseSettingsStore;
use App\Services\Settings\FeatureManager as DatabaseFeatureManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsStore::class, DatabaseSettingsStore::class);
        $this->app->singleton(FeatureManager::class, DatabaseFeatureManager::class);
        $this->app->singleton(AdminAuthenticator::class, LaravelAdminAuthenticator::class);
        $this->app->singleton(UserLifecycle::class, UserLifecycleService::class);
        $this->app->singleton(UserAccessChecker::class, UserAccessService::class);
    }

    public function boot(): void
    {
    }
}

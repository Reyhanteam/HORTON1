<?php

namespace App\Providers;

use App\Contracts\FeatureManager;
use App\Contracts\SettingsStore;
use App\Services\Settings\DatabaseSettingsStore;
use App\Services\Settings\FeatureManager as DatabaseFeatureManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsStore::class, DatabaseSettingsStore::class);
        $this->app->singleton(FeatureManager::class, DatabaseFeatureManager::class);
    }

    public function boot(): void
    {
        // Application bootstrapping belongs here; runtime settings are resolved via contracts.
    }
}

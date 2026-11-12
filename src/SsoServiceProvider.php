<?php

namespace Spamast\Sso;

use Illuminate\Support\ServiceProvider;

class SsoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/sso.php', 'sso');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'sso');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if (config('sso.routes.callback.enabled')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }

        $api = config('sso.routes.hub_api');
        if (($api['enabled'] ?? false) && (class_exists($api['controller'] ?? '') || class_exists($api['summary_controller'] ?? ''))) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config/sso.php' => config_path('sso.php')], 'sso-config');
            $this->publishes([__DIR__ . '/../resources/views' => resource_path('views/vendor/sso')], 'sso-views');
            $this->publishes([__DIR__ . '/../database/migrations' => database_path('migrations')], 'sso-migrations');
            $this->publishes([
                __DIR__ . '/../stubs/HubApiController.php.stub' => app_path('Http/Controllers/Sso/HubApiController.php'),
                __DIR__ . '/../stubs/HubSummaryController.php.stub' => app_path('Http/Controllers/Sso/HubSummaryController.php'),
                __DIR__ . '/../stubs/HubSummaryTest.php.stub' => base_path('tests/Feature/HubSummaryTest.php'),
            ], 'sso-stubs');
        }
    }
}

<?php

namespace Bnb\Laravel\Attachments;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class AttachmentsServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     *
     * @param Router $router
     *
     * @return void
     */
    public function boot(Router $router)
    {

        $this->publishes([
            __DIR__ . '/../config/attachments.php' => config_path('attachments.php')
        ], 'config');

        $this->loadMigrationsFrom(__DIR__ . '/../migrations');

        $this->loadTranslationsFrom(__DIR__.'/../translations', 'attachments');

        if (config('attachments.routes.publish')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
    }


    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/attachments.php', 'attachments');
    }


    private function routes($router)
    {

    }
}

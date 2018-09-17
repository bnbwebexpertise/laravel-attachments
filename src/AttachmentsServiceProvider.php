<?php

namespace Bnb\Laravel\Attachments;

use Bnb\Laravel\Attachments\Console\Commands\CleanupAttachments;
use Illuminate\Support\ServiceProvider;

class AttachmentsServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {

        $this->publishes([
            __DIR__ . '/../config/attachments.php' => config_path('attachments.php')
        ], 'config');

        $this->loadMigrationsFrom(__DIR__ . '/../migrations');

        $this->loadTranslationsFrom(__DIR__.'/../translations', 'attachments');

        if (config('attachments.routes.publish')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanupAttachments::class,
            ]);
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

        // Bind Model to Interface
        $this->app->bind(
            \Bnb\Laravel\Attachments\Contracts\AttachmentContract::class,
            \Bnb\Laravel\Attachments\Attachment::class
        );
    }
}

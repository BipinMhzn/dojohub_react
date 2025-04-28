<?php

namespace BaseSystem;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class BaseSystemProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/base-system.php', 'base-system');
        $this->registerModulesProviders();
        $this->app->register(SystemRouteProvider::class);
        if ($this->app->runningInConsole()) {
            $this->app->register(SystemCommandProvider::class);
        }
    }

    /**
     * Get providers from modules
     */
    protected function registerModulesProviders(): void
    {
        $modulePath = $this->app['config']->get('base-system.module');
        if (is_null($modulePath) || !is_dir(app_path($modulePath))) {
            return;
        }
        $modules = array_map('basename', File::directories(app_path($modulePath)));

        foreach ($modules as $module) {
            //register serviceProvider class in the folder if it exists
            if (File::exists(app_path($modulePath . '/' . $module . '/ServiceProvider.php')) && class_exists('\App\\' . $modulePath . '\\' . $module . '\ServiceProvider')) {
                $this->app->register('\App\\' . $modulePath . '\\' . $module . '\ServiceProvider');
            }
            //look into Providers folder
            $directoryPath = "$modulePath/$module/Providers";
            $providersDirectory = app_path($directoryPath);
            if (File::exists($providersDirectory) && File::isDirectory($providersDirectory)) {
                $files = array_map('basename', File::glob($providersDirectory . '/*.php'));
                //remove the extension
                foreach ($files as &$file) {
                    $file = explode('.', $file)[0];
                    $class = '\App\\' . $directoryPath . '\\' . $file;
                    if (class_exists($class)) {
                        $this->app->register($class);
                    }
                }
            }
        }
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'base-system');
        $this->publishes([
            __DIR__ . '/../config/base-system.php' => config_path('base-system.php'),
            __DIR__ . '/../lang' => $this->app->langPath(),
        ], 'base-system');
    }
}

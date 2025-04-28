<?php

namespace BaseSystem;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class SystemRouteProvider extends RouteServiceProvider
{
    /**
     * @var array
     */
    protected array $modules = [];

    /**
     * RouteServiceProvider constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->init();
    }

    protected function init(): void
    {
        $modulePath = $this->app['config']->get('base-system.module');
        if (is_null($modulePath) || !is_dir(app_path($modulePath))) {
            return;
        }
        $modules = array_map('basename', File::directories(app_path($modulePath)));

        foreach ($modules as $module) {
            $routePathFromApp = app_path("$modulePath/$module/Routes");

            if (File::exists($routePathFromApp) && File::isDirectory($routePathFromApp)) {
                $this->modules[] = (object)[
                    'routePath' => $routePathFromApp,
                    'namespace' => "\\App\\$modulePath\\$module\\Controllers",
                    'hasApi' => File::exists($routePathFromApp . '/api.php'),
                    'hasWeb' => File::exists($routePathFromApp . '/web.php'),
                ];
            }

        }
    }

    public function boot(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
        parent::boot();
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes(): void
    {
        foreach ($this->modules as $module) {
            if ($module->hasApi) {
                Route::group([
                    'middleware' => 'api',
                    'namespace' => $module->namespace,
                    'prefix' => 'api',
                ], static function () use ($module) {
                    require($module->routePath . "/api.php");
                });
            }
        }

    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes(): void
    {
        foreach ($this->modules as $module) {
            if ($module->hasWeb) {
                Route::group([
                    'middleware' => 'web',
                    'namespace' => $module->namespace,
                ], static function () use ($module) {
                    require($module->routePath . "/web.php");
                });
            }
        }
    }
}

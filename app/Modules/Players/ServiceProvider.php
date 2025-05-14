<?php
namespace App\Modules\Players;

use \Illuminate\Support\ServiceProvider as BaseServiceProvider;
use App\Modules\Players\Interfaces\PlayerInterface;
use App\Modules\Players\Repositories\PlayerRepository;

/**
 * Class ServiceProvider
 * @package App\Modules\Players
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * @type bool
     */
    protected bool $defer = true;

    /**
     * Register your binding
     */
    public function register(): void
    {
        $this->app->bind(PlayerInterface::class, PlayerRepository::class);
    }
}

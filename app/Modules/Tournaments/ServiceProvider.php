<?php
namespace App\Modules\Tournaments;

use \Illuminate\Support\ServiceProvider as BaseServiceProvider;
use App\Modules\Tournaments\Interfaces\TournamentInterface;
use App\Modules\Tournaments\Repositories\TournamentRepository;

/**
 * Class ServiceProvider
 * @package App\Modules\Tournaments
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
        $this->app->bind(TournamentInterface::class, TournamentRepository::class);
    }
}

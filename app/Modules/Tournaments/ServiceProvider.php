<?php

namespace App\Modules\Tournaments;

use App\Modules\Tournaments\Interfaces\RegistrationInterface;
use App\Modules\Tournaments\Interfaces\TournamentInterface;
use App\Modules\Tournaments\Repositories\RegistrationRepository;
use App\Modules\Tournaments\Repositories\TournamentRepository;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

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
        $this->app->bind(RegistrationInterface::class, RegistrationRepository::class);
    }
}

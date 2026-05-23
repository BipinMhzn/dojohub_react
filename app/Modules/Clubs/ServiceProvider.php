<?php

namespace App\Modules\Clubs;

use App\Modules\Clubs\Interfaces\ClubCoachInterface;
use App\Modules\Clubs\Interfaces\ClubInterface;
use App\Modules\Clubs\Interfaces\ClubPlayerInterface;
use App\Modules\Clubs\Repositories\ClubCoachRepository;
use App\Modules\Clubs\Repositories\ClubPlayerRepository;
use App\Modules\Clubs\Repositories\ClubRepository;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Class ServiceProvider
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
        $this->app->bind(ClubInterface::class, ClubRepository::class);
        $this->app->bind(ClubPlayerInterface::class, ClubPlayerRepository::class);
        $this->app->bind(ClubCoachInterface::class, ClubCoachRepository::class);
    }
}

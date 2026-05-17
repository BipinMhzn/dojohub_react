<?php

namespace App\Modules\Tournaments;

use App\Modules\Tournaments\Interfaces\BracketInterface;
use App\Modules\Tournaments\Interfaces\BracketServiceInterface;
use App\Modules\Tournaments\Interfaces\CategoryInterface;
use App\Modules\Tournaments\Interfaces\MatchInterface;
use App\Modules\Tournaments\Interfaces\RegistrationInterface;
use App\Modules\Tournaments\Interfaces\TournamentInterface;
use App\Modules\Tournaments\Repositories\BracketRepository;
use App\Modules\Tournaments\Repositories\CategoryRepository;
use App\Modules\Tournaments\Repositories\MatchRepository;
use App\Modules\Tournaments\Repositories\RegistrationRepository;
use App\Modules\Tournaments\Repositories\TournamentRepository;
use App\Modules\Tournaments\Services\BracketService;
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
        $this->app->bind(TournamentInterface::class, TournamentRepository::class);
        $this->app->bind(CategoryInterface::class, CategoryRepository::class);
        $this->app->bind(RegistrationInterface::class, RegistrationRepository::class);
        $this->app->bind(BracketInterface::class, BracketRepository::class);
        $this->app->bind(MatchInterface::class, MatchRepository::class);
        $this->app->bind(BracketServiceInterface::class, BracketService::class);
    }
}

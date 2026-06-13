<?php

namespace App\Modules\Tournaments;

use App\Modules\Tournaments\Interfaces\BracketInterface;
use App\Modules\Tournaments\Interfaces\BracketServiceInterface;
use App\Modules\Tournaments\Interfaces\CategoryStatusTransitionServiceInterface;
use App\Modules\Tournaments\Interfaces\MatchInterface;
use App\Modules\Tournaments\Interfaces\PoolInterface;
use App\Modules\Tournaments\Interfaces\RegistrationInterface;
use App\Modules\Tournaments\Interfaces\TournamentInterface;
use App\Modules\Tournaments\Interfaces\TournamentStatusResolverInterface;
use App\Modules\Tournaments\Interfaces\WeighInInterface;
use App\Modules\Tournaments\Repositories\BracketRepository;
use App\Modules\Tournaments\Repositories\MatchRepository;
use App\Modules\Tournaments\Repositories\PoolRepository;
use App\Modules\Tournaments\Repositories\RegistrationRepository;
use App\Modules\Tournaments\Repositories\TournamentRepository;
use App\Modules\Tournaments\Repositories\WeighInRepository;
use App\Modules\Tournaments\Services\BracketService;
use App\Modules\Tournaments\Services\CategoryStatusTransitionService;
use App\Modules\Tournaments\Services\TournamentStatusResolver;
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
        $this->app->bind(RegistrationInterface::class, RegistrationRepository::class);
        $this->app->bind(BracketInterface::class, BracketRepository::class);
        $this->app->bind(MatchInterface::class, MatchRepository::class);
        $this->app->bind(BracketServiceInterface::class, BracketService::class);
        $this->app->bind(TournamentStatusResolverInterface::class, TournamentStatusResolver::class);
        $this->app->bind(CategoryStatusTransitionServiceInterface::class, CategoryStatusTransitionService::class);
        $this->app->bind(WeighInInterface::class, WeighInRepository::class);
        $this->app->bind(PoolInterface::class, PoolRepository::class);
    }
}

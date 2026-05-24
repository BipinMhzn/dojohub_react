<?php

namespace App\Modules\TournamentCategories;

use App\Modules\TournamentCategories\Interfaces\TournamentCategoryInterface;
use App\Modules\TournamentCategories\Repositories\TournamentCategoryRepository;
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
        $this->app->bind(TournamentCategoryInterface::class, TournamentCategoryRepository::class);
    }
}

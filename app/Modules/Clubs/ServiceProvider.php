<?php
namespace App\Modules\Clubs;

use \Illuminate\Support\ServiceProvider as BaseServiceProvider;
use App\Modules\Clubs\Interfaces\ClubInterface;
use App\Modules\Clubs\Repositories\ClubRepository;

/**
 * Class ServiceProvider
 * @package App\Modules\Clubs
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
    }
}

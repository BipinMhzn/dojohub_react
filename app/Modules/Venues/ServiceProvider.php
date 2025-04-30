<?php
namespace App\Modules\Venues;

use \Illuminate\Support\ServiceProvider as BaseServiceProvider;
use App\Modules\Venues\Interfaces\VenueInterface;
use App\Modules\Venues\Repositories\VenueRepository;

/**
 * Class ServiceProvider
 * @package App\Modules\Venues
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
        $this->app->bind(VenueInterface::class, VenueRepository::class);
    }
}

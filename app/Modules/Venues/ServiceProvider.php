<?php

namespace App\Modules\Venues;

use App\Modules\Venues\Interfaces\VenueInterface;
use App\Modules\Venues\Interfaces\VenueMatInterface;
use App\Modules\Venues\Listeners\VenueEventSubscriber;
use App\Modules\Venues\Models\Address;
use App\Modules\Venues\Models\Venue;
use App\Modules\Venues\Models\VenueMat;
use App\Modules\Venues\Repositories\VenueMatRepository;
use App\Modules\Venues\Repositories\VenueRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    protected bool $defer = false;

    public function register(): void
    {
        $this->app->bind(VenueInterface::class, fn () => new VenueRepository(
            new Venue,
            new Address,
        ));

        $this->app->bind(VenueMatInterface::class, fn () => new VenueMatRepository(
            new VenueMat,
        ));
    }

    public function boot(): void
    {
        Event::subscribe(VenueEventSubscriber::class);
    }
}

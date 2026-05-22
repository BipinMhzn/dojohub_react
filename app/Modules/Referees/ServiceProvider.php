<?php

namespace App\Modules\Referees;

use App\Modules\Referees\Interfaces\RefereeInterface;
use App\Modules\Referees\Models\Referee;
use App\Modules\Referees\Repositories\RefereeRepository;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    protected bool $defer = true;

    public function register(): void
    {
        $this->app->bind(RefereeInterface::class, fn () => new RefereeRepository(new Referee));
    }
}

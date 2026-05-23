<?php

namespace App\Modules\Coaches;

use App\Modules\Coaches\Interfaces\CoachInterface;
use App\Modules\Coaches\Models\Coach;
use App\Modules\Coaches\Repositories\CoachRepository;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    protected bool $defer = true;

    public function register(): void
    {
        $this->app->bind(CoachInterface::class, fn () => new CoachRepository(new Coach));
    }
}

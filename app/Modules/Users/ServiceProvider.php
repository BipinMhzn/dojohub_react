<?php
namespace App\Modules\Users;

use \Illuminate\Support\ServiceProvider as BaseServiceProvider;
use App\Modules\Users\Interfaces\UserInterface;
use App\Modules\Users\Repositories\UserRepository;

/**
 * Class ServiceProvider
 * @package App\Modules\Users
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
        $this->app->bind(UserInterface::class, UserRepository::class);
    }
}

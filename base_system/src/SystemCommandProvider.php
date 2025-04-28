<?php

namespace BaseSystem;

use BaseSystem\Commands\ModuleGenerateCommand;
use Illuminate\Support\ServiceProvider as AbstractServiceProvider;

class SystemCommandProvider extends AbstractServiceProvider
{
    /**
     * @var array|\class-string[]
     */
    protected array $commands = [
        ModuleGenerateCommand::class,
    ];

    public function boot(): void
    {
        $this->commands($this->commands);
    }
}

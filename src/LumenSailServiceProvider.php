<?php

namespace Yackers\LumenSail;

use Yackers\LumenSail\Console\Commands\SailInstallCommand;
use Yackers\LumenSail\Console\Commands\SailPublishCommand;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class LumenSailServiceProvider extends ServiceProvider implements DeferrableProvider
{

    /**
     * Register Sail Console Commands.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([SailInstallCommand::class, SailPublishCommand::class]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [SailInstallCommand::class, SailPublishCommand::class];
    }
}

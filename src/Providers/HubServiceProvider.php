<?php

namespace Yourcormorant\LaravelHubs\Providers;

use Illuminate\Support\ServiceProvider;
use Yourcormorant\LaravelHubs\Console\HubMakeCommand;
use Yourcormorant\LaravelHubs\Console\PipeMakeCommand;

class HubServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if($this->app->runningInConsole()){
            $this->commands([
                HubMakeCommand::class,
                PipeMakeCommand::class,
            ]);
        }
    }
}

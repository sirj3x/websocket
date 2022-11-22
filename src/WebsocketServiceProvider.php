<?php

namespace Sirj3x\Websocket;

use Illuminate\Support\ServiceProvider;
use Sirj3x\Websocket\Console\Commands\MiddlewareMakeCommand;
use Sirj3x\Websocket\Console\Commands\RequestMakeCommand;
use Sirj3x\Websocket\Console\Commands\EventMakeCommand;
use Sirj3x\Websocket\Console\Commands\WebsocketCommand;
use Sirj3x\Websocket\Console\Commands\WebsocketSetupCommand;

class WebsocketServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/websocket.php' => config_path('websocket.php'),
            __DIR__ . '/../routes/websocket.php' => base_path('routes' . DIRECTORY_SEPARATOR . 'websocket.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                WebsocketCommand::class,
                WebsocketSetupCommand::class,
                EventMakeCommand::class,
                MiddlewareMakeCommand::class,
                RequestMakeCommand::class
            ]);
        }
    }
}

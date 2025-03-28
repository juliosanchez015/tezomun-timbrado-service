<?php

namespace Tezomun\TimbradoService;


use Illuminate\Support\ServiceProvider;

class TimbradoServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/timbrado.php', 'timbrado');

        $this->app->singleton('timbrado', function () {
            return new Services\TimbradoService();
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/timbrado.php' => config_path('timbrado.php'),
        ], 'config');
    }
}

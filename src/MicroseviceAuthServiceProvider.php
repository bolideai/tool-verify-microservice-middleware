<?php

namespace Bolideai\VerifyMicroservice;

use Bolideai\VerifyMicroservice\Http\Middleware\VerifyMicroservice;
use Illuminate\Support\ServiceProvider;

class MicroseviceAuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->bootConfig();
        $this->bootMiddlewares();
    }

    /**
     * Boot the config for the package.
     *
     * @return void
     */
    private function bootConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../config/microservice.php' => config_path('microservice.php'),
        ]);
    }

    /**
     * Boot the middlewares for the package.
     *
     * @return void
     */
    private function bootMiddlewares(): void
    {
        $this->app['router']->aliasMiddleware('verify.microservice', VerifyMicroservice::class);
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/microservice.php',
            'microservice'
        );
    }
}

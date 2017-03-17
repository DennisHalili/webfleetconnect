<?php 
namespace WebFleetConnect;

use Illuminate\Support\ServiceProvider;

class WebFleetConnectServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/tomtom.php' => config_path('tomtom.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('tomtom',function($app){
            return WebFleetConnectAPI::getInstance();
        });
    }
}

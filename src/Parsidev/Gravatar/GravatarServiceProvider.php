<?php

namespace Parsidev\Gravatar;

use Illuminate\Support\ServiceProvider;

class GravatarServiceProvider extends ServiceProvider {

    protected $defer = false;

    public function boot() {
		$this->publishes([
            __DIR__ . '/../../config/gravatar.php' => config_path('gravatar.php'),
        ]);
    }

    public function register() {
		$this->app['gravatar'] = $this->app->share(function($app)
        {
            return new Gravatar($this->app['config']);
        });
    }

    public function provides() {
        return ['gravatar'];
    }

}

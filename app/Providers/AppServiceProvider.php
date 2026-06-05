<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Support\Settings;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Make settings + resolved theme available to every view.
        View::composer('*', function ($view) {
            try {
                $settings = Settings::get();
                $view->with('appSettings', $settings)
                     ->with('appTheme', Settings::theme($settings));
            } catch (\Throwable $e) {
                $defaults = Settings::defaults();
                $view->with('appSettings', $defaults)
                     ->with('appTheme', Settings::theme($defaults));
            }
        });
    }
}

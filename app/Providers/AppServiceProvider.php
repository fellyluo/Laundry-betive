<?php

namespace App\Providers;

use App\Support\Settings;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        // Make settings + resolved theme available to every view (per-tenant aware).
        View::composer('*', function ($view) {
            try {
                // Halaman QR pelanggan (/daftar/{user}) -> pakai branding member terkait.
                if (request()->routeIs('register.show', 'register.store') && ($m = request()->route('user'))) {
                    $settings = Settings::get(is_object($m) ? $m->id : (int) $m);
                } else {
                    $settings = Settings::get(); // member -> miliknya; super admin/guest -> platform
                }
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

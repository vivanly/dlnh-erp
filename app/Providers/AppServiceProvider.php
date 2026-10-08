<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Session;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user) {
            return method_exists($user, 'isITDepartment') && $user->isITDepartment() ? true : null;
        });

        // Đồng bộ ngôn ngữ khi đăng nhập thành công
        Event::listen(Login::class, function ($event) {
            $user = $event->user;
            
            if (Session::has('locale')) {
                $user->locale = Session::get('locale');
                $user->save();
            } else {
                Session::put('locale', $user->locale ?? 'vi');
            }
        });
    }
}
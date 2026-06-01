<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

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
        Http::globalOptions([
            'timeout' => 30,
            'force_ip_resolve' => 'v4',
        ]);

        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            $frontend = config('app.frontend_url');

            return $frontend.'/reset-password'
                .'?token='.urlencode($token)
                .'&email='.urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}

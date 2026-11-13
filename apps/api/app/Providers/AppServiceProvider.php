<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\StaffPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        Gate::policy(User::class, StaffPolicy::class);

        RateLimiter::for('login', function (Request $request): Limit {
            $email = Str::lower($request->string('email')->toString());

            return Limit::perMinute(5)->by(Str::transliterate($email.'|'.$request->ip()));
        });

        RateLimiter::for('student-login', function (Request $request): Limit {
            $email = Str::lower($request->string('email')->toString());

            return Limit::perMinute(5)->by(Str::transliterate($email.'|'.$request->ip()));
        });

        RateLimiter::for('student-register', fn (Request $request): Limit => Limit::perHour(5)->by($request->ip()));
        RateLimiter::for('student-password-reset', function (Request $request): Limit {
            $email = Str::lower($request->string('email')->toString());

            return Limit::perHour(5)->by(Str::transliterate($email.'|'.$request->ip()));
        });
    }
}

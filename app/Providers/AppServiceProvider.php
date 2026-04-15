<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Events\CustomerCreated;
use App\Listeners\CustomerCreatedListener;

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
        Event::listen(CustomerCreated::class, CustomerCreatedListener::class);

        RateLimiter::for('partner_api', function (Request $request): Limit {
            $partner = $request->attributes->get('partner');
            $key = $partner ? 'partner:'.$partner->id : $request->ip();

            return Limit::perHour(1000)->by((string) $key)
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'status' => 'error',
                        'error_code' => 'RATE_LIMIT_EXCEEDED',
                        'message' => 'Too many requests',
                    ], 429, $headers);
                });
        });
    }
}

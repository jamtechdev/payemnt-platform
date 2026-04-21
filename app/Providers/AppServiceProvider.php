<?php

namespace App\Providers;

use App\Models\Customer;
use App\Policies\CustomerPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        Gate::policy(Customer::class, CustomerPolicy::class);

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

        Paginator::useBootstrapFive();
    }
}

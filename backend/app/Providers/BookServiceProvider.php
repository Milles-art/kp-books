<?php

namespace App\Providers;

use App\Services\Books\Payments\PaymentGateway;
use App\Services\Books\Payments\SimulatedGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class BookServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(base_path('config/kpbook.php'), 'kpbook');

        $this->app->bind(PaymentGateway::class, function () {
            return match (config('kpbook.default_gateway')) {
                'simulated' => new SimulatedGateway(),
                default => throw new \RuntimeException('Unknown book payment gateway.'),
            };
        });
    }

    public function boot(): void
    {
        // Slows down anyone trying to pull many pages quickly.
        RateLimiter::for('book-pages', function (Request $request) {
            return Limit::perMinute(60)->by($request->user('sanctum')?->id ?: $request->ip());
        });
    }
}

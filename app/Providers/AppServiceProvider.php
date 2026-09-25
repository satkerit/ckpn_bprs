<?php

namespace App\Providers;

use App\Services\Ckpn\BucketClassifier;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        Model::preventLazyLoading(! $this->app->isProduction());

        Password::defaults(function () {
            $rule = Password::min(8)->letters()->numbers();

            return $this->app->isProduction() ? $rule->mixedCase()->symbols()->uncompromised() : $rule;
        });

        $this->configureRateLimiters();
        $this->registerBucketClassifier();
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)->by($request->input('email').'|'.$request->ip()));
    }

    private function registerBucketClassifier(): void
    {
        $this->app->singleton(
            BucketClassifier::class,
            fn () => new BucketClassifier(config('ckpn.perhitungan.bucket_haritgk', [])),
        );
    }
}

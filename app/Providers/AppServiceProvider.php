<?php

namespace App\Providers;

use App\Contracts\Payments\PaymentGateway;
use App\Payments\NullPaymentGateway;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Pennant\Feature;
use Psr\Log\LoggerInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, fn ($app): PaymentGateway => new NullPaymentGateway(
            $app->make(LoggerInterface::class),
            config('powerx_payments.gateway.pending_sign_off', []),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Feature::define(
            config('powerx_growth.ai_assistant.feature', 'powerx-ai-assistant'),
            fn (): bool => (bool) config('powerx_growth.ai_assistant.enabled', false),
        );

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

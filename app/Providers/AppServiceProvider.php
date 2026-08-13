<?php

namespace App\Providers;

use App\Contracts\PaymentService;
use App\Services\StaticQrisPaymentService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentService::class, StaticQrisPaymentService::class);
    }

    public function boot(): void {}
}

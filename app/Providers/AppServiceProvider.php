<?php

namespace App\Providers;

use App\Contracts\PaymentService;
use App\Models\SiteSetting;
use App\Services\ManualPaymentService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentService::class, ManualPaymentService::class);
    }

    public function boot(): void
    {
        View::composer('layouts.app', function ($view): void {
            $view->with('siteSetting', SiteSetting::current());
        });
    }
}

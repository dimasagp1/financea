<?php

namespace App\Providers;

use App\Services\BudgetCalculationService;
use App\Services\OdooSyncService;
use Illuminate\Support\ServiceProvider;

class FinanceMonitoringServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BudgetCalculationService::class, fn () => new BudgetCalculationService());
        $this->app->singleton(OdooSyncService::class, fn () => new OdooSyncService());
    }

    public function boot(): void
    {
    }
}

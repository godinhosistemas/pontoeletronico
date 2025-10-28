<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\TimeEntry;
use App\Observers\TimeEntryObserver;

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
        // Registra o Observer para processar horas extras automaticamente
        TimeEntry::observe(TimeEntryObserver::class);
    }
}

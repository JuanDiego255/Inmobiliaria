<?php

namespace App\Providers;

use App\Models\Tenant;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Listeners;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TenantWithDatabase::class, Tenant::class);
    }

    public function boot(): void
    {
        Event::listen(Events\TenancyInitialized::class, Listeners\BootstrapTenancy::class);
        Event::listen(Events\TenancyEnded::class, Listeners\RevertToCentralContext::class);

        $this->app[\Illuminate\Contracts\Http\Kernel::class]
            ->prependToMiddlewarePriority(\App\Http\Middleware\InitializeTenancy::class);
    }
}

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
        $this->configureEvents();
        $this->makeTenancyMiddlewareHighestPriority();
    }

    protected function configureEvents(): void
    {
        Event::listen(Events\TenantCreated::class, function (Events\TenantCreated $event) {
            $tenant = $event->tenant;
            $manager = app(\Stancl\Tenancy\Database\DatabaseManager::class);
            $manager->createTenantConnection($tenant);
            $manager->getTenantDatabaseManager($tenant)->createDatabase($tenant);

            \Illuminate\Support\Facades\Artisan::call('tenants:migrate', [
                '--tenants' => [$tenant->getTenantKey()],
                '--force' => true,
            ]);
        });

        Event::listen(Events\TenantDeleted::class, function (Events\TenantDeleted $event) {
            try {
                $tenant = $event->tenant;
                $manager = app(\Stancl\Tenancy\Database\DatabaseManager::class);
                $manager->getTenantDatabaseManager($tenant)->deleteDatabase($tenant);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Could not delete tenant DB: " . $e->getMessage());
            }
        });

        Event::listen(Events\TenancyInitialized::class, Listeners\BootstrapTenancy::class);
        Event::listen(Events\TenancyEnded::class, Listeners\RevertToCentralContext::class);
    }

    protected function makeTenancyMiddlewareHighestPriority(): void
    {
        $tenancyMiddleware = \App\Http\Middleware\InitializeTenancy::class;

        $this->app[\Illuminate\Contracts\Http\Kernel::class]
            ->prependToMiddlewarePriority($tenancyMiddleware);
    }
}

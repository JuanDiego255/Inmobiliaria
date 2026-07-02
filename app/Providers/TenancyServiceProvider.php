<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;

class TenancyServiceProvider extends ServiceProvider
{
    public function events(): array
    {
        return [
            Events\CreatingTenant::class => [],
            Events\TenantCreated::class => [
                Jobs\CreateDatabase::class,
                Jobs\MigrateDatabase::class,
            ],
            Events\SavingTenant::class => [],
            Events\TenantSaved::class => [],
            Events\UpdatingTenant::class => [],
            Events\TenantUpdated::class => [],
            Events\DeletingTenant::class => [],
            Events\TenantDeleted::class => [
                Jobs\DeleteDatabase::class,
            ],
            Events\TenancyInitialized::class => [
                Listeners\BootstrapTenancy::class,
            ],
            Events\TenancyEnded::class => [
                Listeners\RevertToCentralContext::class,
            ],
            Events\BootstrapingTenancy::class => [],
            Events\TenancyBootstrapped::class => [],
            Events\RevertingToCentralContext::class => [],
            Events\RevertedToCentralContext::class => [],
        ];
    }

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureEvents();
        $this->makeTenancyMiddlewareHighestPriority();
    }

    protected function configureEvents(): void
    {
        foreach ($this->events() as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof \Closure) {
                    Event::listen($event, $listener);
                } else {
                    Event::listen($event, [$listener, 'handle']);
                }
            }
        }
    }

    protected function makeTenancyMiddlewareHighestPriority(): void
    {
        $tenancyMiddleware = \App\Http\Middleware\InitializeTenancy::class;

        $this->app[\Illuminate\Contracts\Http\Kernel::class]
            ->prependToMiddlewarePriority($tenancyMiddleware);
    }
}

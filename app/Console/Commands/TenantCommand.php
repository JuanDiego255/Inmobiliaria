<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Stancl\Tenancy\Database\Models\Domain;

class TenantCommand extends Command
{
    protected $signature = 'tenant:manage
        {action : create|delete|list|migrate}
        {--id= : Tenant ID (subdomain)}
        {--name= : Tenant name}
        {--email= : Tenant email}
        {--domain= : Full domain (e.g. elite.safeworsolutions.com)}';

    protected $description = 'Manage tenants: create, delete, list, migrate';

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'create' => $this->createTenant(),
            'delete' => $this->deleteTenant(),
            'list' => $this->listTenants(),
            'migrate' => $this->migrateTenant(),
            default => $this->error('Action must be: create, delete, list, or migrate') ?? 1,
        };
    }

    protected function createTenant(): int
    {
        $id = $this->option('id') ?: $this->ask('Tenant ID (será el subdominio)');
        $name = $this->option('name') ?: $this->ask('Nombre del tenant', $id);
        $email = $this->option('email') ?: $this->ask('Email', $id . '@safeworsolutions.com');
        $baseDomain = config('tenancy.base_domain', 'safeworsolutions.com');
        $domain = $this->option('domain') ?: $id . '.' . $baseDomain;

        if (Tenant::find($id)) {
            $this->error("Tenant '{$id}' ya existe.");
            return 1;
        }

        $this->info("Creando tenant '{$id}'...");
        $this->info("  BD: safewors_{$id}");
        $this->info("  Dominio: {$domain}");

        $tenant = Tenant::create([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'plan' => 'basic',
        ]);

        $tenant->domains()->create(['domain' => $domain]);

        $this->info("Tenant '{$id}' creado exitosamente.");
        $this->info("BD: " . ($tenant->database()->getName() ?? "safewors_{$id}"));

        return 0;
    }

    protected function deleteTenant(): int
    {
        $id = $this->option('id') ?: $this->ask('Tenant ID a eliminar');
        $tenant = Tenant::find($id);

        if (! $tenant) {
            $this->error("Tenant '{$id}' no encontrado.");
            return 1;
        }

        if (! $this->confirm("¿Eliminar tenant '{$id}' y su base de datos?")) {
            return 0;
        }

        $tenant->delete();
        $this->info("Tenant '{$id}' eliminado.");

        return 0;
    }

    protected function listTenants(): int
    {
        $tenants = Tenant::with('domains')->get();

        if ($tenants->isEmpty()) {
            $this->info('No hay tenants registrados.');
            return 0;
        }

        $rows = $tenants->map(fn ($t) => [
            $t->id,
            $t->name,
            $t->email,
            $t->plan,
            $t->domains->pluck('domain')->implode(', '),
            $t->database()->getName() ?? '-',
        ])->toArray();

        $this->table(['ID', 'Nombre', 'Email', 'Plan', 'Dominios', 'BD'], $rows);

        return 0;
    }

    protected function migrateTenant(): int
    {
        $id = $this->option('id');

        if ($id) {
            $tenant = Tenant::find($id);
            if (! $tenant) {
                $this->error("Tenant '{$id}' no encontrado.");
                return 1;
            }
            $this->info("Migrando tenant '{$id}'...");
            $tenant->run(function () {
                $this->call('migrate', ['--force' => true]);
            });
            $this->info("Migración completa para '{$id}'.");
        } else {
            $this->info('Migrando todos los tenants...');
            Tenant::all()->each(function ($tenant) {
                $this->info("  → Migrando '{$tenant->id}'...");
                $tenant->run(function () {
                    $this->call('migrate', ['--force' => true]);
                });
            });
            $this->info('Migración completa.');
        }

        return 0;
    }
}

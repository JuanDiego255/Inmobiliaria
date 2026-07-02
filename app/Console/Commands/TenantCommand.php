<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

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

        $dbName = config('tenancy.database.prefix', 'safewors_') . $id . config('tenancy.database.suffix', '');

        $this->info("Creando tenant '{$id}'...");
        $this->info("  BD: {$dbName}");
        $this->info("  Dominio: {$domain}");

        // 1. Create tenant record (HasDatabase trait auto-sets tenancy_db_name)
        $tenant = Tenant::create([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'plan' => 'basic',
        ]);

        // 2. Register domain
        $tenant->domains()->create(['domain' => $domain]);

        // 3. Create the database via SQL
        $this->info('Creando base de datos...');
        $dbCreated = false;
        try {
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $dbCreated = true;
        } catch (\Exception $e) {
            $this->warn("No se pudo crear la BD automáticamente (normal en hosting compartido).");
            $this->warn("Creá la BD '{$dbName}' desde cPanel → MySQL Databases.");
            $this->warn("Asigná tu usuario MySQL a esa BD con todos los privilegios.");
            $this->warn("Luego ejecutá: php artisan tenant:manage migrate --id={$id}");
        }

        // 4. Run tenant migrations (only if DB was created)
        if ($dbCreated) {
            $this->info('Ejecutando migraciones (puede tardar)...');
            try {
                Artisan::call('tenants:migrate', [
                    '--tenants' => [$id],
                    '--force' => true,
                ]);
                $this->info(Artisan::output());
            } catch (\Exception $e) {
                $this->warn("Error en migraciones: {$e->getMessage()}");
                $this->warn("Ejecutá manualmente: php artisan tenant:manage migrate --id={$id}");
            }
        }

        $this->info("Tenant '{$id}' creado exitosamente.");

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

        $dbName = config('tenancy.database.prefix', 'safewors_') . $id . config('tenancy.database.suffix', '');

        $tenant->domains()->delete();
        $tenant->delete();

        try {
            DB::statement("DROP DATABASE IF EXISTS `{$dbName}`");
            $this->info("Tenant '{$id}' y BD '{$dbName}' eliminados.");
        } catch (\Exception $e) {
            $this->info("Tenant '{$id}' eliminado. BD '{$dbName}' debe eliminarse manualmente.");
        }

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
            $t->email ?? '-',
            $t->plan ?? 'basic',
            $t->domains->pluck('domain')->implode(', ') ?: '-',
            config('tenancy.database.prefix', 'safewors_') . $t->id . config('tenancy.database.suffix', ''),
        ])->toArray();

        $this->table(['ID', 'Nombre', 'Email', 'Plan', 'Dominios', 'BD'], $rows);

        return 0;
    }

    protected function migrateTenant(): int
    {
        $id = $this->option('id');

        if ($id) {
            if (! Tenant::find($id)) {
                $this->error("Tenant '{$id}' no encontrado.");
                return 1;
            }
            $this->info("Migrando tenant '{$id}'...");
            Artisan::call('tenants:migrate', [
                '--tenants' => [$id],
                '--force' => true,
            ]);
            $this->info(Artisan::output());
            $this->info("Migración completa para '{$id}'.");
        } else {
            $this->info('Migrando todos los tenants...');
            Artisan::call('tenants:migrate', ['--force' => true]);
            $this->info(Artisan::output());
            $this->info('Migración completa.');
        }

        return 0;
    }
}

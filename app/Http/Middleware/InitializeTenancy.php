<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Botble\Setting\Supports\SettingStore;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Database\Models\Domain;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancy
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', []);

        if (in_array($host, $centralDomains)) {
            return $next($request);
        }

        $tenant = $this->resolveTenant($host);

        if (! $tenant) {
            abort(404, 'Inquilino no encontrado.');
        }

        $dbName = $tenant->tenancy_db_name
            ?? config('tenancy.database.prefix', 'safewors_') . $tenant->getTenantKey() . config('tenancy.database.suffix', '');

        config(['database.connections.mysql.database' => $dbName]);
        DB::purge('mysql');
        DB::reconnect('mysql');

        $this->reloadSettings();

        return $next($request);
    }

    protected function reloadSettings(): void
    {
        try {
            app(SettingStore::class)->load(force: true);
        } catch (\Throwable $e) {
        }
    }

    protected function resolveTenant(string $host): ?Tenant
    {
        $domain = Domain::where('domain', $host)->first();
        if ($domain) {
            return $domain->tenant;
        }

        $baseDomain = config('tenancy.base_domain', 'safeworsolutions.com');
        $subdomain = str_replace('.' . $baseDomain, '', $host);

        if (empty($subdomain) || $subdomain === $host) {
            return null;
        }

        return Tenant::find($subdomain);
    }
}

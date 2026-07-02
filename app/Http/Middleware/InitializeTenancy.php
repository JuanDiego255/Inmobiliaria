<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
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

        $baseDomain = env('TENANCY_BASE_DOMAIN', 'safeworsolutions.com');
        $subdomain = str_replace('.' . $baseDomain, '', $host);

        if (empty($subdomain) || $subdomain === $host) {
            return $next($request);
        }

        $tenant = Tenant::find($subdomain);

        if (! $tenant) {
            abort(404, 'Inquilino no encontrado.');
        }

        tenancy()->initialize($tenant);

        return $next($request);
    }
}

<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Models\TenantFeature;
use Botble\RealEstate\Models\WhatsAppConversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BotDashboardController extends BaseController
{
    public function index(Request $request)
    {
        $currentTenant = $this->detectCurrentTenant();

        if ($currentTenant) {
            $tenantId = $currentTenant->id;
            $tenants = collect([$currentTenant]);
        } else {
            $tenants = DB::connection('mysql')->table('tenants')->orderBy('name')->get();
            $tenantId = $request->query('tenant', $tenants->first()?->id);
        }

        if ($tenantId) {
            $features = TenantFeature::where('tenant_id', $tenantId)->first();

            if (! $features || ! $features->hasFeature('dashboard')) {
                if ($currentTenant) {
                    return redirect()->route('crm.dashboard')
                        ->with('error_msg', 'El dashboard de conversaciones no está habilitado para este tenant.');
                }

                return redirect()->route('crm.bot-flow.index', ['tenant' => $tenantId])
                    ->with('error_msg', 'El dashboard de conversaciones no está habilitado para este tenant.');
            }
        }

        $stats = $this->getStats($tenantId);
        $isTenantView = (bool) $currentTenant;

        page_title()->setTitle('Dashboard del Bot');

        return view('plugins/real-estate::crm.bot-dashboard', compact('tenants', 'tenantId', 'stats', 'isTenantView'));
    }

    public function apiStats(Request $request)
    {
        $currentTenant = $this->detectCurrentTenant();
        $tenantId = $currentTenant ? $currentTenant->id : $request->input('tenant_id');

        if (! $tenantId) {
            return response()->json(['error' => 'tenant_id required'], 400);
        }

        $features = TenantFeature::where('tenant_id', $tenantId)->first();

        if (! $features || ! $features->hasFeature('dashboard')) {
            return response()->json(['error' => 'Dashboard not enabled'], 403);
        }

        return response()->json($this->getStats($tenantId));
    }

    protected function detectCurrentTenant(): ?object
    {
        if (function_exists('tenant') && tenant()) {
            return tenant();
        }

        $host = request()->getHost();
        $domain = DB::connection('mysql')->table('domains')
            ->where('domain', $host)
            ->first();

        if ($domain) {
            return DB::connection('mysql')->table('tenants')
                ->where('id', $domain->tenant_id)
                ->first();
        }

        return null;
    }

    protected function getStats(?string $tenantId): array
    {
        if (! $tenantId) {
            return $this->emptyStats();
        }

        $query = WhatsAppConversation::query()->where('tenant_id', $tenantId);

        $totalConversations = (clone $query)->where('direction', 'inbound')->count();
        $totalResponses = (clone $query)->where('direction', 'outbound')->count();
        $uniqueContacts = (clone $query)->distinct('phone')->count('phone');

        $today = now()->startOfDay();
        $todayConversations = (clone $query)->where('direction', 'inbound')->where('created_at', '>=', $today)->count();

        $last7days = now()->subDays(7)->startOfDay();
        $weekConversations = (clone $query)->where('direction', 'inbound')->where('created_at', '>=', $last7days)->count();

        $last30days = now()->subDays(30)->startOfDay();
        $monthConversations = (clone $query)->where('direction', 'inbound')->where('created_at', '>=', $last30days)->count();

        $flowCompleted = (clone $query)
            ->whereNotNull('metadata')
            ->where('direction', 'outbound')
            ->get()
            ->filter(function ($conv) {
                $meta = $conv->metadata;

                return is_array($meta) && ($meta['flow_state'] ?? '') === 'completed';
            })
            ->count();

        $dailyData = (clone $query)
            ->where('direction', 'inbound')
            ->where('created_at', '>=', $last30days)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $recentConversations = WhatsAppConversation::query()
            ->where('tenant_id', $tenantId)
            ->where('direction', 'inbound')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($conv) {
                return [
                    'phone' => $conv->phone,
                    'message' => mb_substr($conv->message, 0, 100),
                    'created_at' => $conv->created_at->format('d/m/Y H:i'),
                    'time_ago' => $conv->created_at->diffForHumans(),
                ];
            })
            ->toArray();

        return [
            'total_conversations' => $totalConversations,
            'total_responses' => $totalResponses,
            'unique_contacts' => $uniqueContacts,
            'today' => $todayConversations,
            'this_week' => $weekConversations,
            'this_month' => $monthConversations,
            'flows_completed' => $flowCompleted,
            'daily_data' => $dailyData,
            'recent' => $recentConversations,
        ];
    }

    protected function emptyStats(): array
    {
        return [
            'total_conversations' => 0,
            'total_responses' => 0,
            'unique_contacts' => 0,
            'today' => 0,
            'this_week' => 0,
            'this_month' => 0,
            'flows_completed' => 0,
            'daily_data' => [],
            'recent' => [],
        ];
    }
}

<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Base\Facades\PageTitle;
use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Enums\CrmLeadStageEnum;
use Botble\RealEstate\Models\CrmActivity;
use Botble\RealEstate\Models\CrmLead;
use Carbon\Carbon;

class CrmDashboardController extends BaseController
{
    public function index()
    {
        PageTitle::setTitle('CRM Dashboard');

        Assets::addStylesDirectly([
            'vendor/core/plugins/real-estate/css/crm.css',
        ]);
        Assets::addScriptsDirectly([
            'vendor/core/plugins/real-estate/js/crm-modals.js',
            'vendor/core/plugins/real-estate/js/crm-dashboard.js',
        ]);

        $today = Carbon::today();

        $stats = [
            'leads_today' => CrmLead::query()->whereDate('created_at', $today)->count(),
            'pending_followups' => CrmActivity::query()
                ->whereNull('completed_at')
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '>=', $today)
                ->count(),
            'overdue' => CrmActivity::query()
                ->whereNull('completed_at')
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '<', $today)
                ->count(),
            'won_this_month' => CrmLead::query()
                ->where('stage', CrmLeadStageEnum::GANADO)
                ->whereMonth('updated_at', $today->month)
                ->whereYear('updated_at', $today->year)
                ->count(),
        ];

        $recentLeads = CrmLead::query()
            ->with(['assignedAgent', 'currency'])
            ->latest()
            ->limit(10)
            ->get();

        $recentActivities = CrmActivity::query()
            ->with(['lead', 'user'])
            ->latest()
            ->limit(15)
            ->get();

        $pipelineSummary = [];
        foreach (CrmLeadStageEnum::values() as $stage) {
            $pipelineSummary[$stage] = CrmLead::query()->where('stage', $stage)->count();
        }

        $agents = \Botble\RealEstate\Models\Account::query()
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get();

        return view('plugins/real-estate::crm.dashboard', compact(
            'stats',
            'recentLeads',
            'recentActivities',
            'pipelineSummary',
            'agents'
        ));
    }
}

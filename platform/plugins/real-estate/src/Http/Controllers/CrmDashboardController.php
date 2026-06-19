<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\ACL\Models\User;
use Botble\Base\Facades\Assets;
use Botble\Base\Facades\PageTitle;
use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Enums\CrmLeadStageEnum;
use Botble\RealEstate\Enums\CrmTaskStatusEnum;
use Botble\RealEstate\Models\CrmActivity;
use Botble\RealEstate\Models\CrmLead;
use Botble\RealEstate\Models\CrmTask;
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
            'vendor/core/plugins/real-estate/js/crm-tasks.js',
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
        foreach (CrmLeadStageEnum::labels() as $value => $label) {
            $pipelineSummary[$value] = CrmLead::query()->where('stage', $value)->count();
        }

        $agents = \Botble\RealEstate\Models\Account::query()
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get();

        $myTasks = CrmTask::query()
            ->with(['lead'])
            ->where('assigned_to', auth()->id())
            ->whereIn('status', [CrmTaskStatusEnum::PENDING, CrmTaskStatusEnum::IN_PROGRESS])
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END, due_date ASC')
            ->limit(15)
            ->get();

        $adminUsers = User::query()
            ->select('id', 'first_name', 'last_name', 'username')
            ->orderBy('first_name')
            ->get();

        return view('plugins/real-estate::crm.dashboard', compact(
            'stats',
            'recentLeads',
            'recentActivities',
            'pipelineSummary',
            'agents',
            'myTasks',
            'adminUsers'
        ));
    }
}

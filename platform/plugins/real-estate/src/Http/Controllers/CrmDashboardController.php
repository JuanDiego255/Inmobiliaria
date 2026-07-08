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
            'vendor/core/plugins/real-estate/css/crm-dashboard.css',
        ]);
        Assets::addScriptsDirectly([
            'vendor/core/plugins/real-estate/js/crm-modals.js',
            'vendor/core/plugins/real-estate/js/crm-tasks.js',
            'vendor/core/plugins/real-estate/js/crm-dashboard.js',
        ]);
        Assets::addScripts('apexchart')->addStyles('apexchart');

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

        // Conversion funnel data
        $totalLeads = CrmLead::query()->count();
        $funnelStages = ['nuevo', 'contactado', 'calificado', 'en_negociacion', 'ganado'];
        $stageLabels = CrmLeadStageEnum::labels();
        $funnelData = [];
        foreach ($funnelStages as $stage) {
            $count = $pipelineSummary[$stage] ?? 0;
            $funnelData[] = [
                'stage' => $stageLabels[$stage] ?? ucfirst(str_replace('_', ' ', $stage)),
                'value' => $stage,
                'count' => $count,
                'percentage' => $totalLeads > 0 ? round(($count / $totalLeads) * 100, 1) : 0,
            ];
        }

        // Agent performance
        $agentPerformance = [];
        foreach ($agents as $agent) {
            $agentLeads = CrmLead::query()->where('assigned_agent_id', $agent->id);
            $total = (clone $agentLeads)->count();
            $won = (clone $agentLeads)->where('stage', 'ganado')->count();
            $lost = (clone $agentLeads)->where('stage', 'perdido')->count();
            $active = (clone $agentLeads)->whereNotIn('stage', ['ganado', 'perdido'])->count();
            $avgScore = (clone $agentLeads)->avg('score') ?? 0;

            $activitiesCount = CrmActivity::query()
                ->whereIn('lead_id', CrmLead::query()->where('assigned_agent_id', $agent->id)->pluck('id'))
                ->count();

            $tasksCompleted = CrmTask::query()
                ->where('status', 'completed')
                ->whereIn('lead_id', CrmLead::query()->where('assigned_agent_id', $agent->id)->pluck('id'))
                ->count();

            $agentPerformance[] = [
                'id' => $agent->id,
                'name' => $agent->first_name . ' ' . $agent->last_name,
                'total' => $total,
                'won' => $won,
                'lost' => $lost,
                'active' => $active,
                'activities' => $activitiesCount,
                'tasks_completed' => $tasksCompleted,
                'avg_score' => round($avgScore),
                'win_rate' => $total > 0 ? round(($won / $total) * 100, 1) : 0,
            ];
        }

        // Sort by win rate descending
        usort($agentPerformance, fn($a, $b) => $b['win_rate'] <=> $a['win_rate']);

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
            'adminUsers',
            'funnelData',
            'agentPerformance',
            'totalLeads'
        ));
    }
}

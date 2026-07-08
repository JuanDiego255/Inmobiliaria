<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Models\CrmActivity;
use Botble\RealEstate\Models\CrmLead;
use Botble\RealEstate\Models\CrmTask;
use Illuminate\Support\Collection;
use Botble\RealEstate\Models\Account;
use Illuminate\Support\Facades\Auth;

class CrmAutomationService
{
    /**
     * Check for duplicate leads by email or phone.
     * Returns a collection of matching leads.
     */
    public static function findDuplicates(?string $email, ?string $phone, ?int $excludeId = null): Collection
    {
        if (!$email && !$phone) {
            return collect();
        }

        $query = CrmLead::query();

        $query->where(function ($q) use ($email, $phone) {
            if ($email) {
                $q->orWhere('email', $email);
            }
            if ($phone) {
                $q->orWhere('phone', $phone);
            }
        });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->select('id', 'name', 'email', 'phone', 'stage')
            ->limit(5)
            ->get();
    }

    /**
     * Handle post-stage-change automation.
     * Logs an activity and creates a follow-up task.
     */
    public static function onStageChanged(CrmLead $lead, string $oldStage, string $newStage): void
    {
        $userId = Auth::id();

        // Log stage change as activity
        CrmActivity::query()->create([
            'lead_id' => $lead->id,
            'user_id' => $userId,
            'type' => 'note',
            'description' => "Etapa cambiada: {$oldStage} → {$newStage}",
        ]);

        // Auto-create follow-up task based on new stage
        $taskConfig = self::getFollowUpTaskConfig($newStage);
        if ($taskConfig) {
            CrmTask::query()->create([
                'title' => $taskConfig['title'],
                'description' => "Tarea automática para lead: {$lead->name}",
                'lead_id' => $lead->id,
                'assigned_to' => $userId,
                'priority' => $taskConfig['priority'],
                'due_date' => now()->addDays($taskConfig['days'])->toDateString(),
                'status' => 'pending',
            ]);
        }

        // Update last_contacted_at
        $lead->update(['last_contacted_at' => now()]);

        self::recalculateScore($lead);
    }

    /**
     * Get follow-up task configuration for a given stage.
     */
    protected static function getFollowUpTaskConfig(string $stage): ?array
    {
        $configs = [
            'contactado' => [
                'title' => 'Calificar lead',
                'priority' => 'medium',
                'days' => 2,
            ],
            'calificado' => [
                'title' => 'Preparar propuesta comercial',
                'priority' => 'high',
                'days' => 3,
            ],
            'en_negociacion' => [
                'title' => 'Seguimiento de negociación',
                'priority' => 'high',
                'days' => 5,
            ],
            'ganado' => [
                'title' => 'Cerrar documentación y formalizar',
                'priority' => 'urgent',
                'days' => 7,
            ],
        ];

        return $configs[$stage] ?? null;
    }

    /**
     * Log lead creation as an activity.
     */
    public static function onLeadCreated(CrmLead $lead): void
    {
        $userId = Auth::id();
        if (!$userId) return;

        CrmActivity::query()->create([
            'lead_id' => $lead->id,
            'user_id' => $userId,
            'type' => 'note',
            'description' => 'Lead creado en el sistema.',
        ]);

        self::autoAssignAgent($lead);
        $lead->update(['score' => self::calculateScore($lead)]);
    }

    /**
     * Calculate lead score (0-100 scale).
     */
    public static function calculateScore(CrmLead $lead): int
    {
        $score = 0;

        if ($lead->email) $score += 15;
        if ($lead->phone) $score += 15;
        if ($lead->budget_min || $lead->budget_max) $score += 10;
        if ($lead->expected_close_date) $score += 10;
        if ($lead->notes) $score += 5;

        $sourceScores = [
            'referral' => 20,
            'website' => 15, 'consult' => 15,
            'facebook_lead_ad' => 10, 'whatsapp' => 10, 'instagram_dm' => 10, 'messenger' => 10, 'social' => 10,
            'phone' => 8,
            'manual' => 5, 'other' => 5,
        ];
        $sourceValue = $lead->source instanceof \Botble\Base\Supports\Enum ? $lead->source->getValue() : (string) $lead->source;
        $score += $sourceScores[$sourceValue] ?? 5;

        $activityCount = $lead->activities()->count();
        $score += min($activityCount * 5, 15);

        $propertyCount = $lead->properties()->count();
        $score += min($propertyCount * 5, 10);

        return min($score, 100);
    }

    /**
     * Auto-assign lead to least-loaded agent.
     */
    public static function autoAssignAgent(CrmLead $lead): void
    {
        if ($lead->assigned_agent_id) {
            return;
        }

        $agents = Account::query()->select('id')->get();
        if ($agents->isEmpty()) {
            return;
        }

        // Least-loaded: find agent with fewest active leads
        $agentLoads = [];
        foreach ($agents as $agent) {
            $agentLoads[$agent->id] = CrmLead::query()
                ->where('assigned_agent_id', $agent->id)
                ->whereNotIn('stage', ['ganado', 'perdido'])
                ->count();
        }

        asort($agentLoads);
        $bestAgentId = array_key_first($agentLoads);

        if ($bestAgentId) {
            $lead->update(['assigned_agent_id' => $bestAgentId]);
        }
    }

    /**
     * Recalculate and persist lead score.
     */
    public static function recalculateScore(CrmLead $lead): void
    {
        $score = self::calculateScore($lead);
        $lead->update(['score' => $score]);
    }
}

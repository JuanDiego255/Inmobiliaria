<div class="crm-card">
    <div class="crm-card-header">
        <h5 class="crm-card-title">Actividades Recientes</h5>
    </div>
    <div class="crm-card-body">
        <div class="crm-timeline">
            @forelse ($recentActivities as $activity)
                <div class="crm-timeline-item {{ $activity->completed_at ? 'completed' : '' }}">
                    <div class="crm-timeline-icon crm-activity-{{ $activity->type->value }}">
                        <i class="{{ $activity->type->icon() }}"></i>
                    </div>
                    <div class="crm-timeline-content">
                        <div class="crm-timeline-header">
                            <strong class="crm-clickable-lead" data-lead-id="{{ $activity->lead_id }}">{{ $activity->lead?->name }}</strong>
                            <span class="crm-timeline-meta">
                                {{ $activity->user?->name ?: 'Sistema' }} &middot;
                                {{ $activity->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <p class="crm-timeline-desc">{{ Str::limit($activity->description, 120) }}</p>
                        @if ($activity->scheduled_at && !$activity->completed_at)
                            <span class="crm-timeline-badge {{ $activity->scheduled_at->isPast() ? 'overdue' : 'pending' }}">
                                <i class="fas fa-calendar-alt"></i>
                                {{ $activity->scheduled_at->format('d/m/Y H:i') }}
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-muted text-center py-3">No hay actividades registradas.</p>
            @endforelse
        </div>
    </div>
</div>

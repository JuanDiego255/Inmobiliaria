@php
    $activityMaterialIcons = [
        'whatsapp' => 'chat',
        'call' => 'call',
        'email' => 'mail_outline',
        'visit' => 'event_available',
        'meeting' => 'groups',
        'note' => 'sticky_note_2',
    ];
@endphp
<section class="cd-card cd-reveal-up">
    <div class="cd-card-head">
        <div class="cd-ti"><span class="cd-ey">Bitácora</span><h3>Actividades recientes</h3></div>
    </div>
    <div class="cd-card-body">
        @if ($recentActivities->isEmpty())
            <div class="cd-empty">
                <span class="material-icons">history</span>
                <span>No hay actividades registradas.</span>
            </div>
        @else
            <div class="cd-feed">
                @foreach ($recentActivities as $activity)
                    @php
                        $typeVal = $activity->type->getValue();
                    @endphp
                    <div class="cd-act {{ $activity->completed_at ? 'cd-completed' : '' }}">
                        <span class="cd-act-ic" data-k="{{ $typeVal }}">
                            <span class="material-icons">{{ $activityMaterialIcons[$typeVal] ?? 'notes' }}</span>
                        </span>
                        <div class="cd-act-main">
                            <div class="cd-act-top">
                                <span class="cd-act-who crm-clickable-lead" data-lead-id="{{ $activity->lead_id }}">{{ $activity->lead?->name ?? '—' }}</span>
                                <span class="cd-act-when">{{ $activity->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="cd-act-txt">
                                {{ Str::limit($activity->description, 120) }}
                                @if ($activity->user)
                                    · <span class="cd-act-agent">{{ $activity->user->name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

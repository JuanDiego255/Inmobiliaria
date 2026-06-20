@php
    $priorityDotColors = [
        'high' => 'var(--cd-s-perdido)',
        'urgent' => 'var(--cd-s-perdido)',
        'medium' => 'var(--cd-s-calificado)',
        'low' => 'var(--cd-s-ganado)',
    ];
    $priorityLabels = [
        'high' => 'Alta',
        'urgent' => 'Urgente',
        'medium' => 'Media',
        'low' => 'Baja',
    ];
@endphp
<section class="cd-card cd-reveal-up">
    <div class="cd-card-head">
        <div class="cd-ti"><h3>Mis tareas</h3></div>
        <span class="cd-count-pill cd-tnum" id="myTasksCount">{{ $myTasks->count() }}</span>
    </div>
    <div class="cd-card-body" id="myTasksList">
        @forelse ($myTasks as $task)
            @php
                $isDone = $task->status->getValue() === 'completed';
                $isOverdue = $task->due_date && $task->due_date->isPast() && !$isDone;
                $pVal = $task->priority->getValue();
            @endphp
            <div class="cd-task {{ $isDone ? 'cd-done' : '' }}" data-task-id="{{ $task->id }}">
                <button class="cd-task-check crm-task-complete-btn" role="checkbox" aria-checked="{{ $isDone ? 'true' : 'false' }}" data-task-id="{{ $task->id }}">
                    <span class="material-icons">check</span>
                </button>
                <div class="cd-task-main">
                    <div class="cd-task-title">{{ $task->title }}</div>
                    <div class="cd-task-meta">
                        <span class="cd-pdot" style="background:{{ $priorityDotColors[$pVal] ?? 'var(--cd-ink-400)' }}"></span>
                        {{ $priorityLabels[$pVal] ?? $pVal }}
                        @if ($task->due_date)
                            · <span class="{{ $isOverdue ? 'cd-due-late' : '' }}">
                                <span class="material-icons">calendar_today</span>
                                {{ $isOverdue ? 'Vence hoy' : $task->due_date->format('d/m') }}
                            </span>
                        @endif
                        @if ($task->lead)
                            <span class="crm-clickable-lead" data-lead-id="{{ $task->lead_id }}" style="color:var(--cd-accent);cursor:pointer">
                                {{ Str::limit($task->lead->name, 20) }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="cd-empty">
                <span class="material-icons">task_alt</span>
                <span>No hay tareas pendientes.</span>
            </div>
        @endforelse
    </div>
</section>

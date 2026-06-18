<div class="crm-card">
    <div class="crm-card-header">
        <h5 class="crm-card-title"><i class="fas fa-tasks me-2 text-primary"></i>Mis Tareas</h5>
        <span class="badge bg-primary" id="myTasksCount">{{ $myTasks->count() }}</span>
    </div>
    <div class="crm-card-body p-0">
        <div class="crm-tasks-list" id="myTasksList">
            @forelse ($myTasks as $task)
                <div class="crm-task-item {{ $task->due_date && $task->due_date->isPast() ? 'overdue' : '' }}" data-task-id="{{ $task->id }}">
                    <div class="crm-task-check">
                        <button class="btn btn-sm btn-outline-success crm-task-complete-btn" data-task-id="{{ $task->id }}" title="Completar">
                            <i class="fas fa-check"></i>
                        </button>
                    </div>
                    <div class="crm-task-content">
                        <div class="crm-task-title">{{ $task->title }}</div>
                        <div class="crm-task-meta">
                            {!! $task->priority->toHtml() !!}
                            @if ($task->due_date)
                                <span class="crm-task-due {{ $task->due_date->isPast() ? 'text-danger' : 'text-muted' }}">
                                    <i class="far fa-calendar-alt"></i>
                                    {{ $task->due_date->format('d/m') }}
                                </span>
                            @endif
                            @if ($task->lead)
                                <span class="crm-task-lead crm-clickable-lead" data-lead-id="{{ $task->lead_id }}">
                                    <i class="fas fa-user"></i> {{ Str::limit($task->lead->name, 20) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">
                    <i class="fas fa-clipboard-check d-block mb-2" style="font-size:1.5rem;opacity:.3"></i>
                    No hay tareas pendientes.
                </div>
            @endforelse
        </div>
    </div>
</div>

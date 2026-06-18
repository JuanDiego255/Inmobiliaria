<li class="dropdown dropdown-extended dropdown-inbox crm-reminders-dropdown" id="header_crm_reminders_bar">
    <a href="javascript:;" class="dropdown-toggle dropdown-header-name" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-bell"></i>
        <span class="badge badge-default crm-reminder-count" style="{{ $reminderCount > 0 ? '' : 'display:none;' }}">{{ $reminderCount }}</span>
    </a>
    <ul class="dropdown-menu dropdown-menu-end">
        <li class="external">
            <h3>{!! clean('Recordatorios CRM') !!}</h3>
            @if ($reminderCount > 0)
                <a href="javascript:;" class="crm-dismiss-all-reminders">Descartar todos</a>
            @endif
        </li>
        <li>
            <ul class="dropdown-menu-list scroller" style="height: 275px;" data-handle-color="#637283">
                @forelse ($reminders as $reminder)
                    <li class="crm-reminder-item" data-reminder-id="{{ $reminder->id }}">
                        <a href="javascript:;">
                            <span class="photo">
                                <i class="fas fa-bell text-warning"></i>
                            </span>
                            <span class="subject">
                                <span class="from">{{ $reminder->title }}</span>
                                <span class="time">{{ $reminder->remind_at->diffForHumans() }}</span>
                            </span>
                            <span class="message">
                                @if ($reminder->lead)
                                    <i class="fas fa-user fa-sm"></i> {{ $reminder->lead->name }}
                                @endif
                            </span>
                        </a>
                        <button class="btn btn-xs btn-outline-secondary crm-dismiss-reminder" data-reminder-id="{{ $reminder->id }}" title="Descartar">
                            <i class="fas fa-times"></i>
                        </button>
                    </li>
                @empty
                    <li>
                        <a href="javascript:;">
                            <span class="message text-center">No hay recordatorios pendientes.</span>
                        </a>
                    </li>
                @endforelse
            </ul>
        </li>
    </ul>
</li>

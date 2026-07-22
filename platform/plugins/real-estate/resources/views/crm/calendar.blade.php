@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<div class="cd-wrap">

    <header class="cd-head">
        <nav class="cd-crumb">
            <a href="{{ route('dashboard.index') }}">Inicio</a>
            <span class="material-icons">chevron_right</span>
            <a href="{{ route('crm.dashboard') }}">CRM</a>
            <span class="material-icons">chevron_right</span>
            <span class="cd-cur">Calendario</span>
        </nav>
        <div class="cd-head-row">
            <div>
                <span class="cd-eyebrow">CRM · Calendario</span>
                <h1>Calendario</h1>
                <p class="cd-head-sub">Vista unificada de tareas, recordatorios, actividades y eventos de Google Calendar.</p>
            </div>
            <div class="cd-leads-nav" style="gap:8px">
                @if($isGcalConnected)
                    <span class="cal-gcal-badge cal-gcal-on">
                        <span class="material-icons" style="font-size:16px">event</span> Google Calendar conectado
                    </span>
                @else
                    <a href="{{ route('crm.settings.index') }}" class="cal-gcal-badge cal-gcal-off">
                        <span class="material-icons" style="font-size:16px">event_busy</span> Configurar Google Calendar
                    </a>
                @endif
                <button type="button" class="cd-qbtn cd-primary" id="calNewEventBtn" style="width:auto;padding:9px 16px">
                    <span class="cd-qi"><span class="material-icons">add</span></span> Nuevo evento
                </button>
            </div>
        </div>
    </header>

    {{-- Filter bar --}}
    <div class="cal-filters">
        <label class="cal-filter-chip cal-filter-active" data-source="tasks">
            <span class="cal-dot" style="background:#e65100"></span> Tareas
        </label>
        <label class="cal-filter-chip cal-filter-active" data-source="reminders">
            <span class="cal-dot" style="background:#7b1fa2"></span> Recordatorios
        </label>
        <label class="cal-filter-chip cal-filter-active" data-source="activities">
            <span class="cal-dot" style="background:#0288d1"></span> Actividades
        </label>
        <label class="cal-filter-chip cal-filter-active" data-source="leads">
            <span class="cal-dot" style="background:#00897b"></span> Cierres de leads
        </label>
        @if($isGcalConnected)
            <label class="cal-filter-chip cal-filter-active" data-source="google">
                <span class="cal-dot" style="background:#4285f4"></span> Google Calendar
            </label>
        @endif
    </div>

    {{-- Calendar container --}}
    <div class="cd-card" style="padding:20px;min-height:600px">
        <div id="crmCalendar"
            data-events-url="{{ route('crm.calendar.events') }}"
            data-update-url="{{ route('crm.calendar.update-event') }}"
            data-store-url="{{ route('crm.calendar.store-event') }}"
            data-delete-url="{{ url('/') }}/{{ Botble\Base\Facades\BaseHelper::getAdminPrefix() }}/real-estate/crm/calendar/event"
            data-csrf="{{ csrf_token() }}"
            data-gcal="{{ $isGcalConnected ? '1' : '0' }}"
        ></div>
    </div>

    {{-- Legend --}}
    <div class="cal-legend">
        <div class="cal-legend-item"><span class="cal-dot" style="background:#e65100"></span> Tarea pendiente</div>
        <div class="cal-legend-item"><span class="cal-dot" style="background:#1565c0"></span> En progreso</div>
        <div class="cal-legend-item"><span class="cal-dot" style="background:#43a047"></span> Completada</div>
        <div class="cal-legend-item"><span class="cal-dot" style="background:#7b1fa2"></span> Recordatorio</div>
        <div class="cal-legend-item"><span class="cal-dot" style="background:#0288d1"></span> Actividad</div>
        <div class="cal-legend-item"><span class="cal-dot" style="background:#00897b"></span> Cierre lead</div>
        @if($isGcalConnected)
            <div class="cal-legend-item"><span class="cal-dot" style="background:#4285f4"></span> Google Calendar</div>
        @endif
    </div>
</div>

{{-- Event Detail Modal --}}
<div class="modal fade" id="calEventDetailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="calEventDetailTitle">Detalle del Evento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="calEventDetailBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-danger" id="calEventDeleteBtn" style="display:none">
                    <span class="material-icons" style="font-size:16px">delete</span> Eliminar
                </button>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- New Event Modal --}}
<div class="modal fade" id="calNewEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Evento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="calNewEventForm">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="text-title-field">Tipo</label>
                        <select class="form-control" name="type" id="calNewType">
                            <option value="task">Tarea</option>
                            <option value="reminder">Recordatorio</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="text-title-field">Título</label>
                        <input type="text" class="form-control" name="title" id="calNewTitle" required maxlength="300" />
                    </div>
                    <div class="form-group mb-3" id="calNewDescGroup">
                        <label class="text-title-field">Descripción</label>
                        <textarea class="form-control" name="description" id="calNewDesc" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="text-title-field">Fecha / Hora</label>
                                <input type="datetime-local" class="form-control" name="start" id="calNewStart" required />
                            </div>
                        </div>
                        <div class="col-md-6" id="calNewPriorityGroup">
                            <div class="form-group mb-3">
                                <label class="text-title-field">Prioridad</label>
                                <select class="form-control" name="priority">
                                    <option value="low">Baja</option>
                                    <option value="medium" selected>Media</option>
                                    <option value="high">Alta</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6" id="calNewAssignGroup">
                            <div class="form-group mb-3">
                                <label class="text-title-field">Asignar a</label>
                                <select class="form-control" name="assigned_to" id="calNewAssign">
                                    <option value="">— Sin asignar —</option>
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}">{{ $u->first_name }} {{ $u->last_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="text-title-field">Lead (opcional)</label>
                                <select class="form-control" name="lead_id" id="calNewLead">
                                    <option value="">— Ninguno —</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary" id="calNewSubmit">
                        <span class="material-icons" style="font-size:16px">save</span> Crear
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('header')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet" />
@endpush

@push('footer')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/es.global.min.js"></script>
@endpush
@endsection

@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<div class="cd-wrap cd-tasks-page">

    {{-- ═══════════ PAGE HEADER ═══════════ --}}
    <header class="cd-head">
        <nav class="cd-crumb">
            <a href="{{ route('dashboard.index') }}">Inicio</a>
            <span class="material-icons">chevron_right</span>
            <a href="{{ route('crm.dashboard') }}">CRM</a>
            <span class="material-icons">chevron_right</span>
            <span class="cd-cur">Tareas</span>
        </nav>
        <div class="cd-head-row">
            <div>
                <span class="cd-eyebrow">CRM</span>
                <h1>Tareas</h1>
                <p class="cd-head-sub">Gesti&oacute;n completa de tareas del equipo.</p>
            </div>
            <div class="cd-leads-nav">
                <a class="cd-qbtn cd-primary" href="javascript:void(0)" onclick="CRM_openTaskForm()" style="width:auto;padding:9px 16px">
                    <span class="cd-qi"><span class="material-icons">add_task</span></span> Nueva Tarea
                </a>
            </div>
        </div>
    </header>

    {{-- ═══════════ FILTERS ═══════════ --}}
    @php
        $statusOptions = \Botble\RealEstate\Enums\CrmTaskStatusEnum::labels();
        $priorityOptions = \Botble\RealEstate\Enums\CrmTaskPriorityEnum::labels();
    @endphp
    <section class="cd-card" style="margin-bottom:16px;padding:16px 20px;">
        <div class="d-flex flex-wrap gap-3 align-items-end">
            <div>
                <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Estado</label>
                <select class="form-select form-select-sm" id="filterTaskStatus" style="min-width:150px;">
                    <option value="">Todos</option>
                    @foreach ($statusOptions as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Prioridad</label>
                <select class="form-select form-select-sm" id="filterTaskPriority" style="min-width:150px;">
                    <option value="">Todas</option>
                    @foreach ($priorityOptions as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label mb-1" style="font-size:12px;font-weight:600;">Asignado a</label>
                <select class="form-select form-select-sm" id="filterTaskAssigned" style="min-width:180px;">
                    <option value="">Todos</option>
                    @if(isset($users))
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
    </section>

    {{-- ═══════════ TABLE CARD ═══════════ --}}
    <section class="cd-card cd-table-card">
        @include('core/table::base-table')
    </section>
</div>

@include('plugins/real-estate::crm.modals.task-form-modal')
@endsection

@push('header')
<link rel="stylesheet" href="{{ asset('vendor/core/plugins/real-estate/css/crm-dashboard.css') }}">
@endpush

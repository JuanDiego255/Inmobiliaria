@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<div class="cd-wrap">

    {{-- ═══════════ PAGE HEADER ═══════════ --}}
    <header class="cd-head">
        <nav class="cd-crumb">
            <a href="{{ route('dashboard.index') }}">Inicio</a>
            <span class="material-icons">chevron_right</span>
            <a href="{{ route('crm.dashboard') }}">CRM</a>
            <span class="material-icons">chevron_right</span>
            <span class="cd-cur">Recordatorios</span>
        </nav>
        <div class="cd-head-row">
            <div>
                <span class="cd-eyebrow">CRM</span>
                <h1>Recordatorios</h1>
                <p class="cd-head-sub">Gesti&oacute;n de recordatorios y alertas.</p>
            </div>
            <div class="cd-leads-nav">
                <a class="cd-qbtn cd-primary" href="javascript:void(0)" onclick="CRM_openReminderForm()" style="width:auto;padding:9px 16px">
                    <span class="cd-qi"><span class="material-icons">alarm_add</span></span> Nuevo Recordatorio
                </a>
            </div>
        </div>
    </header>

    {{-- ═══════════ FILTER BAR ═══════════ --}}
    <div class="cd-filter-bar" style="display:flex;gap:12px;margin-bottom:16px;">
        <select class="form-select" id="filterEstado" style="max-width:200px;">
            <option value="">Estado: Todos</option>
            <option value="0">Pendiente</option>
            <option value="1">Visto</option>
        </select>
        <select class="form-select" id="filterLead" style="max-width:200px;">
            <option value="">Lead: Todos</option>
        </select>
    </div>

    {{-- ═══════════ TABLE CARD ═══════════ --}}
    <section class="cd-card cd-table-card">
        @include('core/table::base-table')
    </section>
</div>

@include('plugins/real-estate::crm.modals.reminder-form-modal')
@endsection

@push('header')
<link rel="stylesheet" href="{{ asset('vendor/core/plugins/real-estate/css/crm-dashboard.css') }}">
@endpush

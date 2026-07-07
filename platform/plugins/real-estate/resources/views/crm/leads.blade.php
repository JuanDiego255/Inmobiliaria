@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<div class="cd-wrap cd-leads-page">

    {{-- ═══════════ PAGE HEADER ═══════════ --}}
    <header class="cd-head">
        <nav class="cd-crumb">
            <a href="{{ route('dashboard.index') }}">Inicio</a>
            <span class="material-icons">chevron_right</span>
            <a href="{{ route('crm.dashboard') }}">CRM</a>
            <span class="material-icons">chevron_right</span>
            <span class="cd-cur">Leads</span>
        </nav>
        <div class="cd-head-row">
            <div>
                <span class="cd-eyebrow">CRM · Gestión de leads</span>
                <h1>Leads</h1>
                <p class="cd-head-sub">Administrá tus leads con filtros, búsqueda y acciones masivas.</p>
            </div>
            <div class="cd-leads-nav">
                <a class="cd-qbtn" href="{{ route('crm.dashboard') }}" style="width:auto;padding:9px 16px">
                    <span class="cd-qi"><span class="material-icons">dashboard</span></span> Dashboard
                </a>
                <a class="cd-qbtn" href="{{ route('crm.pipeline') }}" style="width:auto;padding:9px 16px">
                    <span class="cd-qi"><span class="material-icons">view_kanban</span></span> Pipeline
                </a>
                <button type="button" class="cd-qbtn cd-primary btn-crm-new-lead" style="width:auto;padding:9px 16px">
                    <span class="cd-qi"><span class="material-icons">person_add</span></span> Nuevo Lead
                </button>
            </div>
        </div>
    </header>

    {{-- ═══════════ TABLE CARD ═══════════ --}}
    <section class="cd-card cd-table-card">
        @include('core/table::base-table')
    </section>
</div>

@include('plugins/real-estate::crm.modals.lead-form-modal', ['agents' => $agents])
@include('plugins/real-estate::crm.modals.lead-detail-modal')
@include('plugins/real-estate::crm.modals.activity-form-modal')
@include('plugins/real-estate::crm.modals.task-form-modal')
@include('plugins/real-estate::crm.modals.reminder-form-modal')
@endsection

@push('footer')
<script>
    $(document).on('click', '.crm-lead-link', function(e) {
        e.preventDefault();
        var leadId = $(this).data('lead-id');
        if (typeof CRM_openLeadDetail === 'function') {
            CRM_openLeadDetail(leadId);
        }
    });
</script>
@endpush

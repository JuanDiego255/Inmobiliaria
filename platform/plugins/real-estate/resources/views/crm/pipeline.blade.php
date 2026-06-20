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
            <span class="cd-cur">Pipeline</span>
        </nav>
        <div class="cd-head-row">
            <div>
                <span class="cd-eyebrow">CRM · Embudo de ventas</span>
                <h1>Pipeline de Ventas</h1>
                <p class="cd-head-sub">Arrastrá los leads entre etapas para actualizar su estado.</p>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                <a class="cd-qbtn" href="{{ route('crm.dashboard') }}" style="width:auto;padding:9px 16px">
                    <span class="cd-qi"><span class="material-icons">dashboard</span></span> Dashboard
                </a>
                <a class="cd-qbtn" href="{{ route('crm.leads.index') }}" style="width:auto;padding:9px 16px">
                    <span class="cd-qi"><span class="material-icons">list_alt</span></span> Tabla
                </a>
                <button type="button" class="cd-qbtn cd-primary btn-crm-new-lead" style="width:auto;padding:9px 16px">
                    <span class="cd-qi"><span class="material-icons">person_add</span></span> Nuevo Lead
                </button>
            </div>
        </div>
    </header>

    {{-- ═══════════ PIPELINE BOARD ═══════════ --}}
    @php
        $stageLabels = \Botble\RealEstate\Enums\CrmLeadStageEnum::labels();
        $stages = array_keys($stageLabels);
        $stageColors = [
            'nuevo' => '--cd-s-nuevo',
            'contactado' => '--cd-s-contactado',
            'calificado' => '--cd-s-calificado',
            'en_negociacion' => '--cd-s-negociacion',
            'ganado' => '--cd-s-ganado',
            'perdido' => '--cd-s-perdido',
        ];
    @endphp

    <div class="cd-pipeline-board" id="crmPipelineBoard">
        @foreach ($stages as $stage)
            <div class="cd-pipeline-col" data-stage="{{ $stage }}">
                <div class="cd-pipeline-col-head">
                    <span class="cd-pipeline-col-dot" style="background:var({{ $stageColors[$stage] ?? '--cd-s-nuevo' }})"></span>
                    <span class="cd-pipeline-col-title">{{ $stageLabels[$stage] }}</span>
                    <span class="cd-pipeline-col-count cd-tnum" data-stage-count="{{ $stage }}">0</span>
                </div>
                <div class="cd-pipeline-col-body cd-drop-zone" data-stage="{{ $stage }}">
                    <div class="cd-pipeline-empty">
                        <span class="material-icons">inbox</span>
                        Sin leads
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

@include('plugins/real-estate::crm.modals.lead-form-modal', ['agents' => $agents])
@include('plugins/real-estate::crm.modals.lead-detail-modal')
@include('plugins/real-estate::crm.modals.activity-form-modal')
@include('plugins/real-estate::crm.modals.task-form-modal')
@include('plugins/real-estate::crm.modals.reminder-form-modal')
@endsection

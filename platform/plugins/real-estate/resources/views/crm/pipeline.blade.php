@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<div class="crm-pipeline-wrapper">
    <div class="crm-pipeline-header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <h4 class="mb-0">Pipeline de Ventas</h4>
                <a href="{{ route('crm.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <a href="{{ route('crm.leads.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-list me-1"></i>Tabla
                </a>
            </div>
            <button type="button" class="btn btn-primary btn-sm btn-crm-new-lead">
                <i class="fas fa-user-plus me-1"></i>Nuevo Lead
            </button>
        </div>
    </div>

    <div class="crm-pipeline-board" id="crmPipelineBoard">
        @php
            $stages = \Botble\RealEstate\Enums\CrmLeadStageEnum::values();
            $stageLabels = \Botble\RealEstate\Enums\CrmLeadStageEnum::labels();
            $stageColors = [
                'nuevo' => '#17a2b8',
                'contactado' => '#6f42c1',
                'calificado' => '#fd7e14',
                'en_negociacion' => '#ffc107',
                'ganado' => '#28a745',
                'perdido' => '#dc3545',
            ];
        @endphp

        @foreach ($stages as $stage)
            <div class="crm-pipeline-column" data-stage="{{ $stage }}">
                <div class="crm-pipeline-column-header" style="border-top-color: {{ $stageColors[$stage] ?? '#6c757d' }}">
                    <span class="crm-pipeline-column-dot" style="background: {{ $stageColors[$stage] ?? '#6c757d' }}"></span>
                    <span class="crm-pipeline-column-title">{{ $stageLabels[$stage] }}</span>
                    <span class="crm-pipeline-column-count badge bg-light text-dark" data-stage-count="{{ $stage }}">0</span>
                </div>
                <div class="crm-pipeline-column-body crm-drop-zone" data-stage="{{ $stage }}">
                    <div class="crm-pipeline-empty text-muted text-center py-4">
                        <i class="fas fa-inbox d-block mb-2" style="font-size:1.5rem;opacity:.3"></i>
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

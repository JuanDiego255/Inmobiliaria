@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<div class="crm-dashboard">
    @include('plugins/real-estate::crm.dashboard.stat-cards')

    <div class="row mt-4">
        <div class="col-lg-8">
            @include('plugins/real-estate::crm.dashboard.my-tasks-today')

            <div class="mt-4">
                @include('plugins/real-estate::crm.dashboard.recent-leads')
            </div>

            <div class="mt-4">
                @include('plugins/real-estate::crm.dashboard.activity-timeline')
            </div>
        </div>
        <div class="col-lg-4">
            @include('plugins/real-estate::crm.dashboard.quick-actions')

            <div class="crm-card mt-4">
                <div class="crm-card-header">
                    <h5 class="crm-card-title">Resumen Pipeline</h5>
                </div>
                <div class="crm-card-body">
                    @foreach ($pipelineSummary as $stage => $count)
                        <div class="crm-pipeline-bar-row">
                            <span class="crm-pipeline-bar-label">{{ \Botble\RealEstate\Enums\CrmLeadStageEnum::labels()[$stage] }}</span>
                            <div class="crm-pipeline-bar-track">
                                <div class="crm-pipeline-bar-fill crm-stage-{{ $stage }}" style="width: {{ $count > 0 ? max(8, min(100, $count * 10)) : 0 }}%"></div>
                            </div>
                            <span class="crm-pipeline-bar-count">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@include('plugins/real-estate::crm.modals.lead-form-modal', ['agents' => $agents])
@include('plugins/real-estate::crm.modals.lead-detail-modal')
@include('plugins/real-estate::crm.modals.activity-form-modal')
@include('plugins/real-estate::crm.modals.task-form-modal', ['adminUsers' => $adminUsers])
@include('plugins/real-estate::crm.modals.reminder-form-modal')
@endsection

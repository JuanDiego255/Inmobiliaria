@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<div class="cd-wrap">

    {{-- ═══════════ PAGE HEADER ═══════════ --}}
    <header class="cd-head">
        <nav class="cd-crumb">
            <a href="{{ route('dashboard.index') }}">Inicio</a>
            <span class="material-icons">chevron_right</span>
            <a href="{{ route('crm.leads.index') }}">CRM</a>
            <span class="material-icons">chevron_right</span>
            <span class="cd-cur">Dashboard</span>
        </nav>
        <div class="cd-head-row">
            <div>
                <span class="cd-eyebrow">CRM · Panel de control</span>
                <h1>Dashboard de CRM</h1>
                <p class="cd-head-sub">Tus leads, tareas y pipeline del día en una sola vista.</p>
            </div>
            <span class="cd-head-meta"><span class="material-icons">calendar_today</span> Hoy · {{ \Carbon\Carbon::now()->translatedFormat('d M Y') }}</span>
        </div>
    </header>

    {{-- ═══════════ KPI ROW ═══════════ --}}
    @include('plugins/real-estate::crm.dashboard.stat-cards')

    {{-- ═══════════ MAIN GRID ═══════════ --}}
    <div class="cd-grid">

        {{-- ───── LEFT STACK ───── --}}
        <div class="cd-col-stack">
            @include('plugins/real-estate::crm.dashboard.my-tasks-today')
            @include('plugins/real-estate::crm.dashboard.recent-leads')
            @include('plugins/real-estate::crm.dashboard.activity-timeline')
        </div>

        {{-- ───── RIGHT STACK ───── --}}
        <div class="cd-col-stack">
            @include('plugins/real-estate::crm.dashboard.quick-actions')

            {{-- Resumen del pipeline --}}
            <section class="cd-card cd-reveal-up">
                <div class="cd-card-head">
                    <div class="cd-ti"><span class="cd-ey">Embudo</span><h3>Resumen del pipeline</h3></div>
                </div>
                <div class="cd-card-body">
                    @php
                        $stageColors = [
                            'nuevo' => '--cd-s-nuevo',
                            'contactado' => '--cd-s-contactado',
                            'calificado' => '--cd-s-calificado',
                            'en_negociacion' => '--cd-s-negociacion',
                            'ganado' => '--cd-s-ganado',
                            'perdido' => '--cd-s-perdido',
                        ];
                        $stageLabels = \Botble\RealEstate\Enums\CrmLeadStageEnum::labels();
                        $pipelineTotal = array_sum($pipelineSummary);
                    @endphp
                    <div class="cd-pipe" id="cd-pipe">
                        @foreach ($pipelineSummary as $stage => $count)
                            <a href="{{ route('crm.pipeline') }}?stage={{ $stage }}" class="cd-pl-row cd-pl-link">
                                <span class="cd-pl-name"><span class="cd-d" style="background:var({{ $stageColors[$stage] ?? '--cd-s-nuevo' }})"></span>{{ $stageLabels[$stage] ?? $stage }}</span>
                                <div class="cd-pl-track"><div class="cd-pl-fill" data-v="{{ $count }}" style="background:var({{ $stageColors[$stage] ?? '--cd-s-nuevo' }})"></div></div>
                                <span class="cd-pl-val cd-tnum">{{ $count }}</span>
                            </a>
                        @endforeach
                    </div>
                    <div class="cd-pl-foot">
                        <span class="cd-l">Leads activos</span>
                        <span class="cd-v cd-tnum">{{ $pipelineTotal }}</span>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

@include('plugins/real-estate::crm.modals.lead-form-modal', ['agents' => $agents])
@include('plugins/real-estate::crm.modals.lead-detail-modal')
@include('plugins/real-estate::crm.modals.activity-form-modal')
@include('plugins/real-estate::crm.modals.task-form-modal', ['adminUsers' => $adminUsers])
@include('plugins/real-estate::crm.modals.reminder-form-modal')
@endsection

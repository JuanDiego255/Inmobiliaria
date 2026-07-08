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

    {{-- ═══════════ CONVERSION FUNNEL ═══════════ --}}
    <section class="cd-card cd-reveal-up" style="margin-top:var(--cd-gap)">
        <div class="cd-card-head">
            <div class="cd-ti">
                <span class="cd-ey">Análisis</span>
                <h3>Embudo de Conversión</h3>
            </div>
            <span class="cd-count-pill">{{ $totalLeads }} leads</span>
        </div>
        <div class="cd-card-body">
            <div id="funnelChart" style="min-height:320px"></div>
        </div>
    </section>

    {{-- ═══════════ AGENT PERFORMANCE ═══════════ --}}
    <section class="cd-card cd-reveal-up" style="margin-top:var(--cd-gap)">
        <div class="cd-card-head">
            <div class="cd-ti">
                <span class="cd-ey">Rendimiento</span>
                <h3>Desempeño por Agente</h3>
            </div>
        </div>
        <div class="cd-card-body" style="padding:0">
            @if(empty($agentPerformance))
                <p style="text-align:center;color:var(--cd-ink-400);padding:40px">No hay agentes con leads asignados.</p>
            @else
            <div style="overflow-x:auto">
                <table class="cd-perf-table">
                    <thead>
                        <tr>
                            <th>Agente</th>
                            <th class="cd-tnum">Leads</th>
                            <th class="cd-tnum">Activos</th>
                            <th class="cd-tnum">Ganados</th>
                            <th class="cd-tnum">Perdidos</th>
                            <th class="cd-tnum">Win Rate</th>
                            <th class="cd-tnum">Actividades</th>
                            <th class="cd-tnum">Tareas OK</th>
                            <th class="cd-tnum">Score Prom.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($agentPerformance as $ap)
                        <tr>
                            <td style="font-weight:600">{{ $ap['name'] }}</td>
                            <td class="cd-tnum">{{ $ap['total'] }}</td>
                            <td class="cd-tnum">{{ $ap['active'] }}</td>
                            <td class="cd-tnum" style="color:var(--cd-s-ganado)">{{ $ap['won'] }}</td>
                            <td class="cd-tnum" style="color:var(--cd-s-perdido)">{{ $ap['lost'] }}</td>
                            <td class="cd-tnum">
                                <span style="display:inline-flex;align-items:center;gap:4px">
                                    <span style="width:36px;height:6px;border-radius:3px;background:var(--cd-line);display:inline-block;position:relative;overflow:hidden">
                                        <span style="position:absolute;left:0;top:0;height:100%;width:{{ min($ap['win_rate'], 100) }}%;background:var(--cd-s-ganado);border-radius:3px"></span>
                                    </span>
                                    {{ $ap['win_rate'] }}%
                                </span>
                            </td>
                            <td class="cd-tnum">{{ $ap['activities'] }}</td>
                            <td class="cd-tnum">{{ $ap['tasks_completed'] }}</td>
                            <td class="cd-tnum">
                                @php
                                    $sc = $ap['avg_score'];
                                    $scColor = $sc >= 76 ? '#10b981' : ($sc >= 51 ? '#f59e0b' : ($sc >= 26 ? '#3b82f6' : '#94a3b8'));
                                @endphp
                                <span style="color:{{ $scColor }};font-weight:600">{{ $sc }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </section>
</div>

<script type="application/json" id="funnelData">@json($funnelData)</script>

@include('plugins/real-estate::crm.modals.lead-form-modal', ['agents' => $agents])
@include('plugins/real-estate::crm.modals.lead-detail-modal')
@include('plugins/real-estate::crm.modals.activity-form-modal')
@include('plugins/real-estate::crm.modals.task-form-modal', ['adminUsers' => $adminUsers])
@include('plugins/real-estate::crm.modals.reminder-form-modal')
@endsection

@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
<div class="cd-wrap">

    <header class="cd-head">
        <nav class="cd-crumb">
            <a href="{{ route('dashboard.index') }}">Inicio</a>
            <span class="material-icons">chevron_right</span>
            <a href="{{ route('crm.dashboard') }}">CRM</a>
            <span class="material-icons">chevron_right</span>
            <span class="cd-cur">Dashboard del Bot</span>
        </nav>
        <div class="cd-head-row">
            <div>
                <span class="cd-eyebrow">CRM · Bot WhatsApp</span>
                <h1>Dashboard de Conversaciones</h1>
                <p class="cd-head-sub">Métricas y actividad del bot por tenant.</p>
            </div>
            <div class="cd-leads-nav">
                <a class="cd-qbtn" href="{{ route('crm.bot-flow.index') }}" style="width:auto;padding:9px 16px">
                    <span class="cd-qi"><span class="material-icons">smart_toy</span></span> Bot Config
                </a>
            </div>
        </div>
    </header>

    {{-- Tenant Selector --}}
    <section class="cd-card cd-settings-card">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-meta">
                <span class="material-icons">business</span>
            </div>
            <div>
                <h3>Seleccionar Empresa</h3>
                <p>Elegí el tenant para ver sus métricas de bot.</p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <select class="form-control" onchange="window.location.href='{{ route('crm.bot-dashboard.index') }}?tenant=' + this.value">
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ $tenantId === $t->id ? 'selected' : '' }}>
                            {{ $t->name }} ({{ $t->id }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    @if($tenantId && !empty($stats))

    {{-- Stats Cards --}}
    <div class="row" style="margin-bottom:20px">
        <div class="col-md-3">
            <div class="cd-card" style="padding:20px;text-align:center">
                <span class="material-icons" style="font-size:32px;color:var(--cd-accent)">chat</span>
                <div style="font-size:28px;font-weight:700;color:var(--cd-ink-900);margin:8px 0 2px" class="cd-tnum">{{ number_format($stats['total_conversations']) }}</div>
                <div style="font-size:12px;color:var(--cd-ink-400)">Total mensajes recibidos</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="cd-card" style="padding:20px;text-align:center">
                <span class="material-icons" style="font-size:32px;color:#10b981">people</span>
                <div style="font-size:28px;font-weight:700;color:var(--cd-ink-900);margin:8px 0 2px" class="cd-tnum">{{ number_format($stats['unique_contacts']) }}</div>
                <div style="font-size:12px;color:var(--cd-ink-400)">Contactos únicos</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="cd-card" style="padding:20px;text-align:center">
                <span class="material-icons" style="font-size:32px;color:#f59e0b">task_alt</span>
                <div style="font-size:28px;font-weight:700;color:var(--cd-ink-900);margin:8px 0 2px" class="cd-tnum">{{ number_format($stats['flows_completed']) }}</div>
                <div style="font-size:12px;color:var(--cd-ink-400)">Flujos completados</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="cd-card" style="padding:20px;text-align:center">
                <span class="material-icons" style="font-size:32px;color:#3b82f6">today</span>
                <div style="font-size:28px;font-weight:700;color:var(--cd-ink-900);margin:8px 0 2px" class="cd-tnum">{{ number_format($stats['today']) }}</div>
                <div style="font-size:12px;color:var(--cd-ink-400)">Mensajes hoy</div>
            </div>
        </div>
    </div>

    {{-- Period Stats --}}
    <div class="row" style="margin-bottom:20px">
        <div class="col-md-4">
            <div class="cd-card" style="padding:16px">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:13px;color:var(--cd-ink-600)">Esta semana</span>
                    <span style="font-size:20px;font-weight:700;color:var(--cd-ink-900)" class="cd-tnum">{{ number_format($stats['this_week']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="cd-card" style="padding:16px">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:13px;color:var(--cd-ink-600)">Este mes</span>
                    <span style="font-size:20px;font-weight:700;color:var(--cd-ink-900)" class="cd-tnum">{{ number_format($stats['this_month']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="cd-card" style="padding:16px">
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:13px;color:var(--cd-ink-600)">Respuestas enviadas</span>
                    <span style="font-size:20px;font-weight:700;color:var(--cd-ink-900)" class="cd-tnum">{{ number_format($stats['total_responses']) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Conversations --}}
    <section class="cd-card cd-settings-card">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-accent">
                <span class="material-icons">forum</span>
            </div>
            <div>
                <h3>Conversaciones Recientes</h3>
                <p>Últimos 20 mensajes recibidos en el bot.</p>
            </div>
        </div>

        @if(empty($stats['recent']))
            <p style="text-align:center;color:var(--cd-ink-400);padding:30px 0">No hay conversaciones registradas aún.</p>
        @else
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead>
                        <tr style="border-bottom:2px solid var(--cd-line)">
                            <th style="padding:10px 12px;text-align:left;font-weight:600;color:var(--cd-ink-600)">Teléfono</th>
                            <th style="padding:10px 12px;text-align:left;font-weight:600;color:var(--cd-ink-600)">Mensaje</th>
                            <th style="padding:10px 12px;text-align:left;font-weight:600;color:var(--cd-ink-600)">Fecha</th>
                            <th style="padding:10px 12px;text-align:right;font-weight:600;color:var(--cd-ink-600)">Hace</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stats['recent'] as $msg)
                        <tr style="border-bottom:1px solid var(--cd-line-soft)">
                            <td style="padding:10px 12px;font-family:monospace;font-size:12px">{{ $msg['phone'] }}</td>
                            <td style="padding:10px 12px;color:var(--cd-ink-600)">{{ $msg['message'] }}</td>
                            <td style="padding:10px 12px;color:var(--cd-ink-400);white-space:nowrap" class="cd-tnum">{{ $msg['created_at'] }}</td>
                            <td style="padding:10px 12px;color:var(--cd-ink-400);text-align:right;white-space:nowrap">{{ $msg['time_ago'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @else
    <section class="cd-card cd-settings-card">
        <p style="text-align:center;color:var(--cd-ink-400);padding:40px">Seleccioná un tenant para ver sus métricas.</p>
    </section>
    @endif

</div>

@push('header')
<link rel="stylesheet" href="{{ asset('vendor/core/plugins/real-estate/css/crm-dashboard.css') }}">
@endpush
@endsection

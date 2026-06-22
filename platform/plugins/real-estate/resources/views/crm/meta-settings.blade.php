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
            <span class="cd-cur">Meta Integración</span>
        </nav>
        <div class="cd-head-row">
            <div>
                <span class="cd-eyebrow">CRM · Integración Meta</span>
                <h1>Meta Leads</h1>
                <p class="cd-head-sub">Capturá leads automáticamente desde Facebook, Instagram, WhatsApp y Messenger.</p>
            </div>
            <div class="cd-leads-nav">
                <a class="cd-qbtn" href="{{ route('crm.dashboard') }}" style="width:auto;padding:9px 16px">
                    <span class="cd-qi"><span class="material-icons">dashboard</span></span> Dashboard
                </a>
                <a class="cd-qbtn" href="{{ route('crm.leads.index') }}" style="width:auto;padding:9px 16px">
                    <span class="cd-qi"><span class="material-icons">people</span></span> Leads
                </a>
            </div>
        </div>
    </header>

    {!! Form::open(['route' => 'crm.meta-settings.update', 'method' => 'POST', 'class' => 'main-setting-form']) !!}

    {{-- ═══════════ WEBHOOK URL INFO ═══════════ --}}
    <section class="cd-card" style="margin-bottom: 24px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
            <span class="material-icons" style="color:#1877F2;font-size:28px">webhook</span>
            <h3 style="margin:0;font-family:'Playfair Display',serif;font-size:1.15rem">Webhook URL</h3>
        </div>
        <p style="color:#666;font-size:.9rem;margin-bottom:12px">
            Copiá esta URL y configurala como Callback URL en tu Meta App (Webhooks).
        </p>
        <div style="display:flex;gap:8px;align-items:center">
            <input type="text" readonly class="form-control" id="metaWebhookUrl"
                value="{{ url('/webhook/meta') }}"
                style="background:#f8f9fa;font-family:monospace;font-size:.85rem" />
            <button type="button" class="cd-qbtn cd-primary" onclick="copyWebhookUrl()" style="width:auto;padding:9px 16px;white-space:nowrap">
                <span class="material-icons" style="font-size:18px">content_copy</span> Copiar
            </button>
        </div>
        <p style="color:#999;font-size:.8rem;margin-top:8px">
            <strong>Verify Token:</strong> <code>{{ setting('crm_meta_verify_token') }}</code>
        </p>
    </section>

    {{-- ═══════════ META APP CONFIG ═══════════ --}}
    <section class="cd-card" style="margin-bottom: 24px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
            <span class="material-icons" style="color:#717fe0;font-size:28px">apps</span>
            <h3 style="margin:0;font-family:'Playfair Display',serif;font-size:1.15rem">Aplicación Meta</h3>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="crm_meta_app_id">App ID</label>
                    <input type="text" class="form-control" id="crm_meta_app_id" name="crm_meta_app_id"
                        value="{{ setting('crm_meta_app_id') }}" placeholder="Ej: 123456789012345" />
                    <small class="form-text text-muted">ID de tu aplicación en Meta Developer Console.</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="crm_meta_app_secret">App Secret</label>
                    <input type="password" class="form-control" id="crm_meta_app_secret" name="crm_meta_app_secret"
                        value="{{ setting('crm_meta_app_secret') }}" placeholder="••••••••" />
                    <small class="form-text text-muted">Se usa para verificar la firma HMAC de los webhooks.</small>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════ TOKENS ═══════════ --}}
    <section class="cd-card" style="margin-bottom: 24px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
            <span class="material-icons" style="color:#25D366;font-size:28px">key</span>
            <h3 style="margin:0;font-family:'Playfair Display',serif;font-size:1.15rem">Tokens de Acceso</h3>
        </div>

        <div class="form-group mb-3">
            <label class="text-title-field" for="crm_meta_page_access_token">Page Access Token (Long-lived)</label>
            <textarea class="form-control" id="crm_meta_page_access_token" name="crm_meta_page_access_token"
                rows="3" placeholder="EAA...">{{ setting('crm_meta_page_access_token') }}</textarea>
            <small class="form-text text-muted">Token de acceso de la página de Facebook. Se necesita para obtener datos de Lead Ads y nombres de contactos de Messenger.</small>
        </div>

        <div class="form-group mb-3">
            <label class="text-title-field" for="crm_meta_verify_token">Verify Token (Webhook)</label>
            <input type="text" class="form-control" id="crm_meta_verify_token" name="crm_meta_verify_token"
                value="{{ setting('crm_meta_verify_token') }}" />
            <small class="form-text text-muted">Token que Meta envía para verificar el webhook. Se genera automáticamente.</small>
        </div>
    </section>

    {{-- ═══════════ WHATSAPP ═══════════ --}}
    <section class="cd-card" style="margin-bottom: 24px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
            <span style="color:#25D366;font-size:24px"><i class="fab fa-whatsapp"></i></span>
            <h3 style="margin:0;font-family:'Playfair Display',serif;font-size:1.15rem">WhatsApp Business</h3>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="crm_meta_whatsapp_phone_id">Phone Number ID</label>
                    <input type="text" class="form-control" id="crm_meta_whatsapp_phone_id" name="crm_meta_whatsapp_phone_id"
                        value="{{ setting('crm_meta_whatsapp_phone_id') }}" placeholder="Ej: 109876543210" />
                    <small class="form-text text-muted">ID del número de teléfono de WhatsApp Business.</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="crm_meta_whatsapp_biz_id">WhatsApp Business Account ID</label>
                    <input type="text" class="form-control" id="crm_meta_whatsapp_biz_id" name="crm_meta_whatsapp_biz_id"
                        value="{{ setting('crm_meta_whatsapp_biz_id') }}" placeholder="Ej: 112233445566" />
                    <small class="form-text text-muted">ID de la cuenta de WhatsApp Business.</small>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════ CRM CONFIG ═══════════ --}}
    <section class="cd-card" style="margin-bottom: 24px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
            <span class="material-icons" style="color:#717fe0;font-size:28px">settings</span>
            <h3 style="margin:0;font-family:'Playfair Display',serif;font-size:1.15rem">Configuración CRM</h3>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="crm_meta_default_agent_id">Agente por defecto</label>
                    <div class="ui-select-wrapper">
                        <select class="ui-select form-control" id="crm_meta_default_agent_id" name="crm_meta_default_agent_id">
                            <option value="">— Sin asignar —</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}" @selected(setting('crm_meta_default_agent_id') == $agent->id)>
                                    {{ $agent->first_name }} {{ $agent->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <small class="form-text text-muted">Agente al que se asignarán automáticamente los leads de Meta.</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group mb-3" style="padding-top:28px">
                    <label class="form-check-label" style="display:flex;align-items:center;gap:10px;cursor:pointer">
                        <input type="hidden" name="crm_meta_auto_import" value="0" />
                        <input type="checkbox" class="form-check-input" name="crm_meta_auto_import" value="1"
                            @checked(setting('crm_meta_auto_import')) style="width:20px;height:20px" />
                        <span>
                            <strong>Auto-importar leads</strong><br>
                            <small class="text-muted">Si está activo, los leads se crean automáticamente al recibir webhooks.</small>
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════ SAVE BUTTON ═══════════ --}}
    <div style="display:flex;justify-content:flex-end;margin-bottom:24px">
        <button type="submit" class="cd-qbtn cd-primary" style="width:auto;padding:12px 32px;font-size:1rem">
            <span class="material-icons" style="font-size:20px">save</span> Guardar configuración
        </button>
    </div>

    {!! Form::close() !!}

    {{-- ═══════════ WEBHOOK LOGS ═══════════ --}}
    <section class="cd-card" style="margin-bottom: 24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
            <div style="display:flex;align-items:center;gap:12px">
                <span class="material-icons" style="color:#717fe0;font-size:28px">list_alt</span>
                <h3 style="margin:0;font-family:'Playfair Display',serif;font-size:1.15rem">Últimos Webhooks</h3>
            </div>
            <button type="button" class="cd-qbtn" onclick="loadWebhookLogs()" style="width:auto;padding:6px 14px;font-size:.85rem">
                <span class="material-icons" style="font-size:16px">refresh</span> Actualizar
            </button>
        </div>
        <div id="webhookLogsContainer">
            <p style="color:#999;font-size:.9rem">Cargando logs...</p>
        </div>
    </section>

    {{-- ═══════════ SETUP GUIDE ═══════════ --}}
    <section class="cd-card" style="margin-bottom: 24px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
            <span class="material-icons" style="color:#1877F2;font-size:28px">menu_book</span>
            <h3 style="margin:0;font-family:'Playfair Display',serif;font-size:1.15rem">Guía de Configuración</h3>
        </div>
        <div style="color:#555;font-size:.9rem;line-height:1.7">
            <ol style="padding-left:20px">
                <li><strong>Crear App en Meta:</strong> Ir a <code>developers.facebook.com</code> → My Apps → Create App → Business → Completar datos.</li>
                <li><strong>Agregar productos:</strong> En el panel de la App, agregar: <em>Webhooks</em>, <em>Facebook Login</em> (si no existe), <em>WhatsApp</em> (si aplica), <em>Messenger</em> (si aplica).</li>
                <li><strong>Configurar Webhook:</strong> En Webhooks → subscribir a <em>Page</em> → Callback URL: la URL de arriba → Verify Token: el token de arriba → Subscribir a <code>leadgen</code> y <code>messages</code>.</li>
                <li><strong>Generar Page Token:</strong> En Facebook Login → ir a Graph API Explorer → seleccionar tu Page → generar token → extender a long-lived → pegar arriba.</li>
                <li><strong>WhatsApp (opcional):</strong> En WhatsApp → Getting Started → copiar Phone Number ID y Business Account ID → pegar arriba. Configurar webhook callback para mensajes.</li>
                <li><strong>Lead Ads:</strong> Subscribir tu página al webhook de leadgen en la sección de Webhooks de la App.</li>
                <li><strong>Activar:</strong> Marcar "Auto-importar leads" arriba y guardar.</li>
            </ol>
        </div>
    </section>
</div>

@push('footer')
<script>
    function copyWebhookUrl() {
        var input = document.getElementById('metaWebhookUrl');
        input.select();
        input.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(input.value).then(function() {
            Botble.showSuccess('URL copiada al portapapeles');
        });
    }

    function loadWebhookLogs() {
        var container = document.getElementById('webhookLogsContainer');
        container.innerHTML = '<p style="color:#999;font-size:.9rem">Cargando...</p>';

        $.ajax({
            url: '{{ route("crm.meta-logs") }}',
            method: 'GET',
            success: function(res) {
                var logs = res.data || [];
                if (!logs.length) {
                    container.innerHTML = '<p style="color:#999;font-size:.9rem">No hay webhooks registrados aún.</p>';
                    return;
                }
                var html = '<table style="width:100%;font-size:.85rem;border-collapse:collapse">';
                html += '<thead><tr style="border-bottom:2px solid #eee;text-align:left">';
                html += '<th style="padding:8px 6px">ID</th>';
                html += '<th style="padding:8px 6px">Tipo</th>';
                html += '<th style="padding:8px 6px">Estado</th>';
                html += '<th style="padding:8px 6px">Error</th>';
                html += '<th style="padding:8px 6px">Fecha</th>';
                html += '<th style="padding:8px 6px"></th>';
                html += '</tr></thead><tbody>';
                logs.forEach(function(log) {
                    var status = log.processed
                        ? '<span style="color:#25D366">✓ Procesado</span>'
                        : (log.error_message ? '<span style="color:#E4405F">✗ Error</span>' : '<span style="color:#999">Pendiente</span>');
                    html += '<tr style="border-bottom:1px solid #f0f0f0">';
                    html += '<td style="padding:6px">#' + log.id + '</td>';
                    html += '<td style="padding:6px"><code>' + log.event_type + '</code></td>';
                    html += '<td style="padding:6px">' + status + '</td>';
                    html += '<td style="padding:6px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + (log.error_message || '—') + '</td>';
                    html += '<td style="padding:6px">' + (log.created_at || '') + '</td>';
                    html += '<td style="padding:6px">';
                    if (!log.processed && log.error_message) {
                        html += '<button type="button" class="cd-qbtn" style="padding:3px 10px;font-size:.8rem" onclick="retryLog(' + log.id + ')">Reintentar</button>';
                    }
                    html += '</td></tr>';
                });
                html += '</tbody></table>';
                container.innerHTML = html;
            },
            error: function() {
                container.innerHTML = '<p style="color:#E4405F;font-size:.9rem">Error al cargar logs.</p>';
            }
        });
    }

    function retryLog(id) {
        $.ajax({
            url: '{{ url("/") }}/{{ Botble\Base\Facades\BaseHelper::getAdminPrefix() }}/real-estate/crm/meta-logs/' + id + '/retry',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(res) {
                Botble.showSuccess(res.message || 'Reenviado.');
                loadWebhookLogs();
            },
            error: function() {
                Botble.showError('Error al reintentar.');
            }
        });
    }

    $(document).ready(function() {
        loadWebhookLogs();
    });
</script>
@endpush
@endsection

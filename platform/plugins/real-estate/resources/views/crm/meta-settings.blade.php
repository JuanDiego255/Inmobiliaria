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
                <a class="cd-qbtn cd-primary" href="{{ route('crm.meta-help') }}" style="width:auto;padding:9px 16px">
                    <span class="cd-qi"><span class="material-icons">help_outline</span></span> Ayuda
                </a>
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
    <section class="cd-card cd-settings-card">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-meta">
                <span class="material-icons">webhook</span>
            </div>
            <div>
                <h3>Webhook URL</h3>
                <p>Copiá esta URL y configurala como Callback URL en tu Meta App (Webhooks).</p>
            </div>
        </div>

        <div class="cd-webhook-url-box">
            <input type="text" readonly id="metaWebhookUrl" value="{{ url('/webhook/meta') }}" />
            <button type="button" class="cd-copy-btn" onclick="copyWebhookUrl()">
                <span class="material-icons" style="font-size:16px">content_copy</span> Copiar
            </button>
        </div>
        <div class="cd-verify-token">
            <strong>Verify Token:</strong> <code>{{ setting('crm_meta_verify_token') }}</code>
        </div>
    </section>

    {{-- ═══════════ META APP CONFIG ═══════════ --}}
    <section class="cd-card cd-settings-card">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-accent">
                <span class="material-icons">apps</span>
            </div>
            <div>
                <h3>Aplicación Meta</h3>
                <p>Credenciales de tu app en Meta Developer Console.</p>
            </div>
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
    <section class="cd-card cd-settings-card">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-green">
                <span class="material-icons">key</span>
            </div>
            <div>
                <h3>Tokens de Acceso</h3>
                <p>Tokens necesarios para comunicarse con la Graph API de Meta.</p>
            </div>
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
    <section class="cd-card cd-settings-card">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-green">
                <i class="fab fa-whatsapp" style="font-size:22px"></i>
            </div>
            <div>
                <h3>WhatsApp Business</h3>
                <p>Configuración para capturar leads desde mensajes de WhatsApp.</p>
            </div>
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
    <section class="cd-card cd-settings-card">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-accent">
                <span class="material-icons">settings</span>
            </div>
            <div>
                <h3>Configuración CRM</h3>
                <p>Opciones de asignación y comportamiento automático.</p>
            </div>
        </div>

        <div class="row" style="align-items:stretch">
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
            <div class="col-md-6" style="display:flex;align-items:center">
                <label class="cd-toggle-wrap">
                    <input type="hidden" name="crm_meta_auto_import" value="0" />
                    <input type="checkbox" name="crm_meta_auto_import" value="1"
                        @checked(setting('crm_meta_auto_import')) />
                    <div class="cd-toggle-text">
                        <strong>Auto-importar leads</strong><br>
                        <small>Si está activo, los leads se crean automáticamente al recibir webhooks.</small>
                    </div>
                </label>
            </div>
        </div>
    </section>

    {{-- ═══════════ WHATSAPP BOT AI ═══════════ --}}
    <section class="cd-card cd-settings-card">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-green">
                <span class="material-icons">smart_toy</span>
            </div>
            <div>
                <h3>Bot de WhatsApp con IA</h3>
                <p>Responde automáticamente consultas sobre propiedades usando inteligencia artificial.</p>
            </div>
        </div>

        <div style="background:linear-gradient(135deg,#e8f5e9,#e3f2fd);border-radius:10px;padding:16px 20px;margin-bottom:20px;border-left:4px solid #2e7d32">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                <span class="material-icons" style="color:#2e7d32;font-size:20px">hub</span>
                <strong style="color:#1b5e20;font-size:.92rem">Bot Centralizado Multi-Tenant</strong>
            </div>
            <p style="color:#33691e;font-size:.84rem;margin:0;line-height:1.5">
                Este bot opera desde un solo número de WhatsApp para todas las empresas inmobiliarias (tenants).
                Al recibir un mensaje, el bot presenta las empresas disponibles y el cliente elige con cuál consultar.
                Las propiedades se buscan en la base de datos del tenant seleccionado y los leads se crean automáticamente en su CRM.
            </p>
        </div>

        <div class="row" style="margin-bottom:16px">
            <div class="col-md-6">
                <label class="cd-toggle-wrap">
                    <input type="hidden" name="crm_whatsapp_bot_enabled" value="0" />
                    <input type="checkbox" name="crm_whatsapp_bot_enabled" value="1"
                        @checked(setting('crm_whatsapp_bot_enabled')) />
                    <div class="cd-toggle-text">
                        <strong>Activar Bot IA</strong><br>
                        <small>Cuando está activo, el bot responde mensajes de WhatsApp automáticamente con datos de propiedades.</small>
                    </div>
                </label>
            </div>
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="crm_whatsapp_bot_max_properties">Máx. propiedades por respuesta</label>
                    <input type="number" class="form-control" id="crm_whatsapp_bot_max_properties"
                        name="crm_whatsapp_bot_max_properties"
                        value="{{ setting('crm_whatsapp_bot_max_properties', 5) }}"
                        min="1" max="10" />
                    <small class="form-text text-muted">Cantidad máxima de propiedades que el bot muestra en cada respuesta.</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="crm_whatsapp_bot_llm_provider">Proveedor de IA</label>
                    <div class="ui-select-wrapper">
                        <select class="ui-select form-control" id="crm_whatsapp_bot_llm_provider" name="crm_whatsapp_bot_llm_provider">
                            <option value="claude" @selected(setting('crm_whatsapp_bot_llm_provider', 'claude') === 'claude')>
                                Claude (Anthropic) — Recomendado
                            </option>
                            <option value="openai" @selected(setting('crm_whatsapp_bot_llm_provider') === 'openai')>
                                OpenAI (GPT)
                            </option>
                        </select>
                    </div>
                    <small class="form-text text-muted">Claude Haiku es el más recomendado por calidad/precio (~$0.004/conversación).</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="crm_whatsapp_bot_llm_model">Modelo</label>
                    <input type="text" class="form-control" id="crm_whatsapp_bot_llm_model"
                        name="crm_whatsapp_bot_llm_model"
                        value="{{ setting('crm_whatsapp_bot_llm_model', 'claude-haiku-4-5-20251001') }}"
                        placeholder="claude-haiku-4-5-20251001" />
                    <small class="form-text text-muted">Claude: claude-haiku-4-5-20251001 · OpenAI: gpt-4o-mini</small>
                </div>
            </div>
        </div>

        <div class="form-group mb-3">
            <label class="text-title-field" for="crm_whatsapp_bot_llm_api_key">API Key del LLM</label>
            <input type="password" class="form-control" id="crm_whatsapp_bot_llm_api_key"
                name="crm_whatsapp_bot_llm_api_key"
                value="{{ setting('crm_whatsapp_bot_llm_api_key') }}"
                placeholder="sk-ant-... o sk-..." />
            <small class="form-text text-muted">Clave de API de Anthropic o OpenAI. Se obtiene en la consola del proveedor.</small>
        </div>

        <div class="form-group mb-3">
            <label class="text-title-field" for="crm_whatsapp_bot_system_prompt">Instrucciones del bot (System Prompt)</label>
            <textarea class="form-control" id="crm_whatsapp_bot_system_prompt"
                name="crm_whatsapp_bot_system_prompt"
                rows="6" placeholder="Dejá vacío para usar el prompt por defecto...">{{ setting('crm_whatsapp_bot_system_prompt') }}</textarea>
            <small class="form-text text-muted">Personalizá el comportamiento del bot. Dejá vacío para usar las instrucciones por defecto (recomendado).</small>
        </div>

        <div class="form-group mb-3">
            <label class="text-title-field" for="crm_whatsapp_bot_welcome_message">Mensaje de fallback</label>
            <input type="text" class="form-control" id="crm_whatsapp_bot_welcome_message"
                name="crm_whatsapp_bot_welcome_message"
                value="{{ setting('crm_whatsapp_bot_welcome_message') }}"
                placeholder="¡Hola! Gracias por contactarnos. Un agente te atenderá pronto." />
            <small class="form-text text-muted">Mensaje que se envía si la IA no está disponible o falla.</small>
        </div>
    </section>

    {{-- ═══════════ SAVE BUTTON ═══════════ --}}
    <div class="cd-save-row">
        <button type="submit" class="cd-save-btn">
            <span class="material-icons" style="font-size:20px">save</span> Guardar configuración
        </button>
    </div>

    {!! Form::close() !!}

    {{-- ═══════════ WEBHOOK LOGS ═══════════ --}}
    <section class="cd-card cd-settings-card">
        <div class="cd-sc-head" style="justify-content:space-between">
            <div style="display:flex;align-items:center;gap:14px">
                <div class="cd-sc-icon cd-sc-accent">
                    <span class="material-icons">list_alt</span>
                </div>
                <div>
                    <h3>Últimos Webhooks</h3>
                    <p>Log de eventos recibidos desde Meta.</p>
                </div>
            </div>
            <button type="button" class="cd-qbtn" onclick="loadWebhookLogs()" style="width:auto;padding:7px 16px;font-size:.82rem">
                <span class="material-icons" style="font-size:16px">refresh</span> Actualizar
            </button>
        </div>
        <div id="webhookLogsContainer">
            <p style="color:var(--cd-ink-400);font-size:.88rem">Cargando logs...</p>
        </div>
    </section>

    {{-- ═══════════ HELP LINK ═══════════ --}}
    <section class="cd-card cd-settings-card" style="text-align:center;padding:32px">
        <span class="material-icons" style="font-size:40px;color:var(--cd-accent);margin-bottom:12px">menu_book</span>
        <h3 style="font-family:'Playfair Display',serif;font-size:1.1rem;margin-bottom:8px">¿Necesitás ayuda para configurar?</h3>
        <p style="color:var(--cd-ink-400);font-size:.88rem;margin-bottom:16px">Tutorial detallado paso a paso para conectar Facebook, Instagram y WhatsApp.</p>
        <a href="{{ route('crm.meta-help') }}" class="cd-save-btn" style="display:inline-flex;text-decoration:none">
            <span class="material-icons" style="font-size:20px">help_outline</span> Ver Guía Completa
        </a>
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
        container.innerHTML = '<p style="color:var(--cd-ink-400);font-size:.88rem">Cargando...</p>';

        $.ajax({
            url: '{{ route("crm.meta-logs") }}',
            method: 'GET',
            success: function(res) {
                var logs = res.data || [];
                if (!logs.length) {
                    container.innerHTML = '<p style="color:var(--cd-ink-400);font-size:.88rem">No hay webhooks registrados aún.</p>';
                    return;
                }
                var html = '<table class="cd-logs-table">';
                html += '<thead><tr>';
                html += '<th>ID</th>';
                html += '<th>Tipo</th>';
                html += '<th>Estado</th>';
                html += '<th>Error</th>';
                html += '<th>Fecha</th>';
                html += '<th></th>';
                html += '</tr></thead><tbody>';
                logs.forEach(function(log) {
                    var status = log.processed
                        ? '<span class="cd-log-ok">✓ Procesado</span>'
                        : (log.error_message ? '<span class="cd-log-err">✗ Error</span>' : '<span class="cd-log-pending">Pendiente</span>');
                    html += '<tr>';
                    html += '<td>#' + log.id + '</td>';
                    html += '<td><code>' + log.event_type + '</code></td>';
                    html += '<td>' + status + '</td>';
                    html += '<td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + (log.error_message || '—') + '</td>';
                    html += '<td>' + (log.created_at || '') + '</td>';
                    html += '<td>';
                    if (!log.processed && log.error_message) {
                        html += '<button type="button" class="cd-qbtn" style="padding:4px 12px;font-size:.78rem" onclick="retryLog(' + log.id + ')">Reintentar</button>';
                    }
                    html += '</td></tr>';
                });
                html += '</tbody></table>';
                container.innerHTML = html;
            },
            error: function() {
                container.innerHTML = '<p style="color:#E4405F;font-size:.88rem">Error al cargar logs.</p>';
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

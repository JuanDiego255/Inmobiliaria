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
            <span class="cd-cur">Configuración</span>
        </nav>
        <div class="cd-head-row">
            <div>
                <span class="cd-eyebrow">CRM · Configuración</span>
                <h1>Configuración del CRM</h1>
                <p class="cd-head-sub">Integraciones y ajustes generales del módulo CRM.</p>
            </div>
            <div class="cd-leads-nav">
                <a class="cd-qbtn" href="{{ route('crm.dashboard') }}" style="width:auto;padding:9px 16px">
                    <span class="cd-qi"><span class="material-icons">dashboard</span></span> Dashboard
                </a>
            </div>
        </div>
    </header>

    @if(session('success_msg'))
        <div class="alert alert-success" style="border-radius:10px;margin-bottom:20px;display:flex;align-items:center;gap:10px;padding:14px 20px">
            <span class="material-icons" style="color:#2e7d32">check_circle</span>
            {{ session('success_msg') }}
        </div>
    @endif

    @if(session('error_msg'))
        <div class="alert alert-danger" style="border-radius:10px;margin-bottom:20px;display:flex;align-items:center;gap:10px;padding:14px 20px">
            <span class="material-icons" style="color:#c62828">error</span>
            {{ session('error_msg') }}
        </div>
    @endif

    {{-- ═══════════ GOOGLE CALENDAR STATUS ═══════════ --}}
    <section class="cd-card cd-settings-card">
        <div class="cd-sc-head">
            <div class="cd-sc-icon" style="background:linear-gradient(135deg,#4285f4,#34a853);color:#fff">
                <span class="material-icons">event</span>
            </div>
            <div>
                <h3>Google Calendar</h3>
                <p>Sincronizá tareas y recordatorios del CRM con Google Calendar automáticamente.</p>
            </div>
            @if($isConnected)
                <span style="margin-left:auto;background:#e8f5e9;color:#2e7d32;padding:6px 16px;border-radius:20px;font-size:.82rem;font-weight:600;display:flex;align-items:center;gap:6px">
                    <span class="material-icons" style="font-size:16px">check_circle</span> Conectado
                </span>
            @else
                <span style="margin-left:auto;background:#fce4ec;color:#c62828;padding:6px 16px;border-radius:20px;font-size:.82rem;font-weight:600;display:flex;align-items:center;gap:6px">
                    <span class="material-icons" style="font-size:16px">cancel</span> No conectado
                </span>
            @endif
        </div>
    </section>

    {!! Form::open(['route' => 'crm.settings.update', 'method' => 'POST', 'class' => 'main-setting-form']) !!}

    {{-- ═══════════ GOOGLE CREDENTIALS ═══════════ --}}
    <section class="cd-card cd-settings-card">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-accent">
                <span class="material-icons">key</span>
            </div>
            <div>
                <h3>Credenciales OAuth 2.0</h3>
                <p>Credenciales de tu proyecto en Google Cloud Console.</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="crm_gcal_client_id">Client ID</label>
                    <input type="text" class="form-control" id="crm_gcal_client_id" name="crm_gcal_client_id"
                        value="{{ setting('crm_gcal_client_id') }}" placeholder="xxxxxx.apps.googleusercontent.com" />
                    <small class="form-text text-muted">Se obtiene en Google Cloud Console → APIs & Services → Credentials.</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="crm_gcal_client_secret">Client Secret</label>
                    <input type="password" class="form-control" id="crm_gcal_client_secret" name="crm_gcal_client_secret"
                        value="{{ setting('crm_gcal_client_secret') }}" placeholder="GOCSPX-..." />
                    <small class="form-text text-muted">Secret de la credencial OAuth 2.0.</small>
                </div>
            </div>
        </div>

        <div class="form-group mb-3">
            <label class="text-title-field" for="crm_gcal_calendar_id">Calendar ID (opcional)</label>
            <input type="text" class="form-control" id="crm_gcal_calendar_id" name="crm_gcal_calendar_id"
                value="{{ setting('crm_gcal_calendar_id') }}" placeholder="primary" />
            <small class="form-text text-muted">Dejá vacío o escribí <code>primary</code> para usar el calendario principal. También podés usar el ID de un calendario específico (ej: <code>abc123@group.calendar.google.com</code>).</small>
        </div>

        <div class="cd-webhook-url-box" style="margin-top:10px;margin-bottom:0">
            <label style="font-size:.82rem;color:var(--cd-ink-400);margin-bottom:4px;display:block">URI de redirección autorizada (copiá esto a Google Cloud Console)</label>
            <input type="text" readonly id="gcalRedirectUri"
                value="{{ url('/') }}/{{ Botble\Base\Facades\BaseHelper::getAdminPrefix() }}/real-estate/crm/settings/google-calendar/callback" />
            <button type="button" class="cd-copy-btn" onclick="copyRedirectUri()">
                <span class="material-icons" style="font-size:16px">content_copy</span> Copiar
            </button>
        </div>
    </section>

    {{-- ═══════════ SYNC OPTIONS ═══════════ --}}
    <section class="cd-card cd-settings-card">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-green">
                <span class="material-icons">sync</span>
            </div>
            <div>
                <h3>Sincronización</h3>
                <p>Elegí qué elementos del CRM se sincronizan con Google Calendar.</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <label class="cd-toggle-wrap">
                    <input type="hidden" name="crm_gcal_sync_tasks" value="0" />
                    <input type="checkbox" name="crm_gcal_sync_tasks" value="1"
                        @checked(setting('crm_gcal_sync_tasks')) />
                    <div class="cd-toggle-text">
                        <strong>Sincronizar Tareas</strong><br>
                        <small>Las nuevas tareas con fecha de vencimiento se crean como eventos en Google Calendar.</small>
                    </div>
                </label>
            </div>
            <div class="col-md-6">
                <label class="cd-toggle-wrap">
                    <input type="hidden" name="crm_gcal_sync_reminders" value="0" />
                    <input type="checkbox" name="crm_gcal_sync_reminders" value="1"
                        @checked(setting('crm_gcal_sync_reminders')) />
                    <div class="cd-toggle-text">
                        <strong>Sincronizar Recordatorios</strong><br>
                        <small>Los nuevos recordatorios se crean como eventos en Google Calendar.</small>
                    </div>
                </label>
            </div>
        </div>
    </section>

    {{-- ═══════════ SAVE BUTTON ═══════════ --}}
    <div class="cd-save-row">
        <button type="submit" class="cd-save-btn">
            <span class="material-icons" style="font-size:20px">save</span> Guardar configuración
        </button>
    </div>

    {!! Form::close() !!}

    {{-- ═══════════ CONNECT / DISCONNECT ═══════════ --}}
    <section class="cd-card cd-settings-card">
        <div class="cd-sc-head">
            <div class="cd-sc-icon" style="background:linear-gradient(135deg,#4285f4,#0f9d58);color:#fff">
                <span class="material-icons">link</span>
            </div>
            <div>
                <h3>Conexión con Google</h3>
                <p>Autorizá el acceso a Google Calendar para que el CRM pueda crear eventos.</p>
            </div>
        </div>

        @if($isConnected)
            <div style="background:linear-gradient(135deg,#e8f5e9,#e3f2fd);border-radius:10px;padding:16px 20px;margin-bottom:16px;border-left:4px solid #2e7d32">
                <div style="display:flex;align-items:center;gap:8px">
                    <span class="material-icons" style="color:#2e7d32;font-size:20px">check_circle</span>
                    <strong style="color:#1b5e20;font-size:.92rem">Google Calendar está conectado</strong>
                </div>
                <p style="color:#33691e;font-size:.84rem;margin:6px 0 0;line-height:1.5">
                    Las tareas y recordatorios se sincronizarán automáticamente según la configuración de arriba.
                </p>
            </div>
            <a href="{{ route('crm.settings.gcal-disconnect') }}" class="cd-qbtn" style="width:auto;padding:9px 20px;color:#c62828"
                onclick="return confirm('¿Desconectar Google Calendar? Los eventos ya creados no se eliminarán.')">
                <span class="material-icons" style="font-size:16px">link_off</span> Desconectar Google Calendar
            </a>
        @else
            @if($authUrl)
                <div style="background:linear-gradient(135deg,#fff3e0,#fce4ec);border-radius:10px;padding:16px 20px;margin-bottom:16px;border-left:4px solid #e65100">
                    <p style="color:#bf360c;font-size:.84rem;margin:0;line-height:1.5">
                        <strong>Paso final:</strong> Las credenciales están configuradas. Hacé clic en el botón de abajo para autorizar el acceso a Google Calendar.
                    </p>
                </div>
                <a href="{{ $authUrl }}" class="cd-save-btn" style="display:inline-flex;text-decoration:none;background:linear-gradient(135deg,#4285f4,#34a853)">
                    <span class="material-icons" style="font-size:20px">login</span> Conectar con Google Calendar
                </a>
            @else
                <div style="background:#f5f5f5;border-radius:10px;padding:16px 20px;border-left:4px solid #9e9e9e">
                    <p style="color:#616161;font-size:.84rem;margin:0;line-height:1.5">
                        Primero completá el <strong>Client ID</strong> y <strong>Client Secret</strong> de arriba, guardá la configuración, y luego aparecerá el botón para conectar con Google.
                    </p>
                </div>
            @endif
        @endif
    </section>

    {{-- ═══════════ STEP BY STEP GUIDE ═══════════ --}}
    <section class="cd-card cd-settings-card" id="guia-setup">
        <div class="cd-sc-head" style="cursor:pointer" onclick="toggleGuide()">
            <div class="cd-sc-icon" style="background:linear-gradient(135deg,#1565c0,#0d47a1);color:#fff">
                <span class="material-icons">menu_book</span>
            </div>
            <div>
                <h3>Guía paso a paso: Configurar Google Calendar</h3>
                <p>Tutorial completo para vincular Google Calendar con el CRM.</p>
            </div>
            <span class="material-icons" id="guideArrow" style="margin-left:auto;font-size:28px;color:var(--cd-ink-400);transition:transform .3s">expand_more</span>
        </div>

        <div id="guideContent" style="display:none">

            {{-- STEP 1 --}}
            <div class="gcal-step">
                <div class="gcal-step-num">1</div>
                <div class="gcal-step-body">
                    <h4>Crear un proyecto en Google Cloud Console</h4>
                    <ol>
                        <li>Andá a <a href="https://console.cloud.google.com/" target="_blank" rel="noopener">console.cloud.google.com</a></li>
                        <li>Si no tenés un proyecto, hacé clic en <strong>"Select a project"</strong> → <strong>"New Project"</strong></li>
                        <li>Ponele un nombre (ej: <code>CRM Calendar</code>) y hacé clic en <strong>"Create"</strong></li>
                        <li>Asegurate de tener el proyecto seleccionado en el menú superior</li>
                    </ol>
                </div>
            </div>

            {{-- STEP 2 --}}
            <div class="gcal-step">
                <div class="gcal-step-num">2</div>
                <div class="gcal-step-body">
                    <h4>Habilitar la API de Google Calendar</h4>
                    <ol>
                        <li>En el menú lateral, andá a <strong>"APIs & Services"</strong> → <strong>"Library"</strong></li>
                        <li>Buscá <strong>"Google Calendar API"</strong></li>
                        <li>Hacé clic en el resultado y luego en <strong>"Enable"</strong></li>
                    </ol>
                </div>
            </div>

            {{-- STEP 3 --}}
            <div class="gcal-step">
                <div class="gcal-step-num">3</div>
                <div class="gcal-step-body">
                    <h4>Configurar pantalla de consentimiento OAuth</h4>
                    <ol>
                        <li>Andá a <strong>"APIs & Services"</strong> → <strong>"OAuth consent screen"</strong></li>
                        <li>Seleccioná <strong>"External"</strong> (para cualquier cuenta Google) o <strong>"Internal"</strong> (solo cuentas de tu organización)</li>
                        <li>Completá los campos obligatorios:
                            <ul>
                                <li><strong>App name:</strong> nombre de tu empresa (ej: <code>CRM Inmobiliaria</code>)</li>
                                <li><strong>User support email:</strong> tu correo</li>
                                <li><strong>Developer contact:</strong> tu correo</li>
                            </ul>
                        </li>
                        <li>En <strong>"Scopes"</strong>, hacé clic en <strong>"Add or remove scopes"</strong> y buscá <code>Google Calendar API - .../auth/calendar.events</code>. Seleccionalo y guardá</li>
                        <li>En <strong>"Test users"</strong>, agregá la cuenta de Google que vas a usar para el calendario</li>
                        <li>Guardá y continuá</li>
                    </ol>
                    <div class="gcal-note">
                        <span class="material-icons">info</span>
                        <span>Mientras la app esté en modo "Testing", solo los usuarios agregados como test users podrán autorizarla. Para producción, podés solicitar verificación a Google.</span>
                    </div>
                </div>
            </div>

            {{-- STEP 4 --}}
            <div class="gcal-step">
                <div class="gcal-step-num">4</div>
                <div class="gcal-step-body">
                    <h4>Crear credenciales OAuth 2.0</h4>
                    <ol>
                        <li>Andá a <strong>"APIs & Services"</strong> → <strong>"Credentials"</strong></li>
                        <li>Hacé clic en <strong>"+ CREATE CREDENTIALS"</strong> → <strong>"OAuth client ID"</strong></li>
                        <li>En <strong>"Application type"</strong> seleccioná <strong>"Web application"</strong></li>
                        <li>En <strong>"Name"</strong> poné algo como <code>CRM Web</code></li>
                        <li>En <strong>"Authorized redirect URIs"</strong>, hacé clic en <strong>"+ ADD URI"</strong> y pegá esta URL:</li>
                    </ol>
                    <div class="gcal-uri-box">
                        <code>{{ url('/') }}/{{ Botble\Base\Facades\BaseHelper::getAdminPrefix() }}/real-estate/crm/settings/google-calendar/callback</code>
                    </div>
                    <ol start="6">
                        <li>Hacé clic en <strong>"Create"</strong></li>
                        <li>Se mostrará el <strong>Client ID</strong> y <strong>Client Secret</strong> — copialos</li>
                    </ol>
                </div>
            </div>

            {{-- STEP 5 --}}
            <div class="gcal-step">
                <div class="gcal-step-num">5</div>
                <div class="gcal-step-body">
                    <h4>Configurar en el CRM</h4>
                    <ol>
                        <li>Pegá el <strong>Client ID</strong> y <strong>Client Secret</strong> en los campos de arriba</li>
                        <li>Activá las casillas de <strong>"Sincronizar Tareas"</strong> y/o <strong>"Sincronizar Recordatorios"</strong></li>
                        <li>Hacé clic en <strong>"Guardar configuración"</strong></li>
                        <li>Aparecerá el botón <strong>"Conectar con Google Calendar"</strong> — hacé clic</li>
                        <li>Autorizá el acceso con tu cuenta de Google</li>
                        <li>Si ves un aviso de "App no verificada", hacé clic en <strong>"Advanced"</strong> → <strong>"Go to CRM (unsafe)"</strong> (es seguro, es tu propia app)</li>
                    </ol>
                    <div class="gcal-note gcal-note-success">
                        <span class="material-icons">celebration</span>
                        <span><strong>Listo.</strong> A partir de ahora, las tareas y recordatorios que creés en el CRM aparecerán automáticamente en Google Calendar.</span>
                    </div>
                </div>
            </div>

            {{-- STEP 6 - Optional --}}
            <div class="gcal-step">
                <div class="gcal-step-num" style="background:var(--cd-ink-300)">?</div>
                <div class="gcal-step-body">
                    <h4>Usar un calendario específico (opcional)</h4>
                    <p>Por defecto se usa el calendario principal de la cuenta. Si querés usar uno diferente:</p>
                    <ol>
                        <li>Abrí <a href="https://calendar.google.com/calendar/r/settings" target="_blank" rel="noopener">Google Calendar</a> → Settings</li>
                        <li>En la lista de calendarios, hacé clic en el calendario deseado</li>
                        <li>Buscá <strong>"Calendar ID"</strong> (algo como <code>abc123@group.calendar.google.com</code>)</li>
                        <li>Pegá ese ID en el campo <strong>"Calendar ID"</strong> de arriba</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
    .gcal-step {
        display: flex;
        gap: 16px;
        padding: 20px 0;
        border-bottom: 1px solid rgba(0,0,0,.06);
    }
    .gcal-step:last-child { border-bottom: none; }
    .gcal-step-num {
        flex-shrink: 0;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4285f4, #34a853);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: .95rem;
    }
    .gcal-step-body { flex: 1; }
    .gcal-step-body h4 {
        font-size: .95rem;
        font-weight: 700;
        margin: 4px 0 10px;
        color: var(--cd-ink-900, #1a1a2e);
    }
    .gcal-step-body ol, .gcal-step-body ul {
        margin: 0;
        padding-left: 20px;
    }
    .gcal-step-body li {
        font-size: .86rem;
        line-height: 1.7;
        color: var(--cd-ink-600, #444);
    }
    .gcal-step-body p {
        font-size: .86rem;
        color: var(--cd-ink-600, #444);
        margin: 0 0 10px;
    }
    .gcal-step-body code {
        background: rgba(66,133,244,.08);
        color: #1565c0;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: .82rem;
    }
    .gcal-note {
        background: #e3f2fd;
        border-radius: 8px;
        padding: 12px 16px;
        margin-top: 12px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: .82rem;
        color: #1565c0;
        line-height: 1.5;
    }
    .gcal-note .material-icons { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
    .gcal-note-success {
        background: #e8f5e9;
        color: #2e7d32;
    }
    .gcal-uri-box {
        background: #f5f5f5;
        border: 1px dashed #bdbdbd;
        border-radius: 8px;
        padding: 10px 16px;
        margin: 10px 0;
        word-break: break-all;
    }
    .gcal-uri-box code {
        background: none;
        color: #333;
        padding: 0;
        font-size: .82rem;
    }
</style>

@push('footer')
<script>
    function copyRedirectUri() {
        var input = document.getElementById('gcalRedirectUri');
        input.select();
        input.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(input.value).then(function() {
            Botble.showSuccess('URI de redirección copiada al portapapeles');
        });
    }

    function toggleGuide() {
        var content = document.getElementById('guideContent');
        var arrow = document.getElementById('guideArrow');
        if (content.style.display === 'none') {
            content.style.display = 'block';
            arrow.style.transform = 'rotate(180deg)';
        } else {
            content.style.display = 'none';
            arrow.style.transform = 'rotate(0deg)';
        }
    }
</script>
@endpush
@endsection

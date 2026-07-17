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
            <span class="cd-cur">Entrenamiento del Bot</span>
        </nav>
        <div class="cd-head-row">
            <div>
                <span class="cd-eyebrow">CRM · Bot WhatsApp</span>
                <h1>Entrenamiento del Bot</h1>
                <p class="cd-head-sub">Configurá flujos de conversación y correos por tenant. Sin flujo activo, el bot usa IA libre.</p>
            </div>
            <div class="cd-leads-nav">
                <a class="cd-qbtn" href="{{ route('crm.meta-settings') }}" style="width:auto;padding:9px 16px">
                    <span class="cd-qi"><span class="material-icons">settings</span></span> Meta Config
                </a>
                <a class="cd-qbtn" href="{{ route('crm.dashboard') }}" style="width:auto;padding:9px 16px">
                    <span class="cd-qi"><span class="material-icons">dashboard</span></span> Dashboard
                </a>
            </div>
        </div>
    </header>

    @if(session('success_msg'))
    <div class="alert alert-success alert-dismissible fade show" style="border-radius:var(--cd-radius-sm);border:none;background:#ecfdf5;color:#065f46;padding:14px 18px;margin-bottom:20px">
        <span class="material-icons" style="font-size:18px;vertical-align:middle;margin-right:6px">check_circle</span>
        {{ session('success_msg') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error_msg'))
    <div class="alert alert-danger alert-dismissible fade show" style="border-radius:var(--cd-radius-sm);border:none;background:#fef2f2;color:#991b1b;padding:14px 18px;margin-bottom:20px">
        <span class="material-icons" style="font-size:18px;vertical-align:middle;margin-right:6px">error</span>
        {{ session('error_msg') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- ═══════════ VEHICLE MODE (Central) ═══════════ --}}
    <section class="cd-card cd-settings-card">
        <div class="cd-sc-head">
            <div class="cd-sc-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff">
                <span class="material-icons">directions_car</span>
            </div>
            <div>
                <h3>Modo Vehículos — Concesionario</h3>
                <p>Activá este modo para que el bot busque vehículos en vez de propiedades. Ideal para concesionarios.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('crm.bot-flow.save-vehicle-settings') }}">
            @csrf
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label class="text-title-field">Activar bot para vehículos</label>
                        <div>
                            <label class="cd-toggle-label" style="display:inline-flex;align-items:center;gap:10px;cursor:pointer">
                                <input type="hidden" name="crm_whatsapp_bot_vehicle_mode" value="0">
                                <input type="checkbox" name="crm_whatsapp_bot_vehicle_mode" value="1"
                                    class="cd-toggle-input"
                                    {{ setting('crm_whatsapp_bot_vehicle_mode') ? 'checked' : '' }}>
                                <span class="cd-toggle-switch"></span>
                                <span>{{ setting('crm_whatsapp_bot_vehicle_mode') ? 'Modo vehículos activo' : 'Modo inmobiliario (actual)' }}</span>
                            </label>
                        </div>
                        <small class="text-muted">Si está activo, el bot buscará vehículos vía API externa. Si no, mantiene el flujo inmobiliario.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label class="text-title-field" for="vehicle_dealer_name">Nombre del concesionario (Filial)</label>
                        <input type="text" class="form-control" id="vehicle_dealer_name" name="crm_whatsapp_bot_vehicle_dealer_name"
                            value="{{ setting('crm_whatsapp_bot_vehicle_dealer_name', 'Autos Grecia') }}"
                            placeholder="Autos Grecia">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-3">
                        <label class="text-title-field" for="vehicle_api_url">URL del API de Vehículos</label>
                        <input type="url" class="form-control" id="vehicle_api_url" name="crm_vehicle_api_url"
                            value="{{ setting('crm_vehicle_api_url') }}"
                            placeholder="https://space360cr.com">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="text-title-field" for="vehicle_api_token">Token del API</label>
                        <input type="password" class="form-control" id="vehicle_api_token" name="crm_vehicle_api_token"
                            value="{{ setting('crm_vehicle_api_token') }}"
                            placeholder="Bearer token del API de vehículos">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="text-title-field" for="vehicle_system_prompt">System prompt personalizado (opcional)</label>
                        <textarea class="form-control" id="vehicle_system_prompt" name="crm_whatsapp_bot_vehicle_system_prompt"
                            rows="3" placeholder="Dejá vacío para usar el prompt por defecto del concesionario">{{ setting('crm_whatsapp_bot_vehicle_system_prompt') }}</textarea>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:10px;align-items:center">
                <button type="submit" class="btn btn-warning" style="display:inline-flex;align-items:center;gap:6px">
                    <span class="material-icons" style="font-size:18px">save</span> Guardar configuración de vehículos
                </button>
                @if(setting('crm_whatsapp_bot_vehicle_mode'))
                    <span class="badge" style="background:#f59e0b;color:#fff;padding:6px 12px;border-radius:20px;font-size:12px">
                        <span class="material-icons" style="font-size:14px;vertical-align:middle">directions_car</span>
                        Filial: {{ setting('crm_whatsapp_bot_vehicle_dealer_name', 'Autos Grecia') }}
                    </span>
                @endif
            </div>
        </form>
    </section>

    {{-- ═══════════ TENANT SELECTOR ═══════════ --}}
    <section class="cd-card cd-settings-card">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-meta">
                <span class="material-icons">business</span>
            </div>
            <div>
                <h3>Seleccionar Empresa</h3>
                <p>Elegí el tenant para configurar su flujo de bot y notificaciones.</p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-0">
                    <select class="form-control" id="tenantSelector" onchange="switchTenant(this.value)">
                        @foreach($tenants as $t)
                            <option value="{{ $t->id }}" {{ $tenantId === $t->id ? 'selected' : '' }}>
                                {{ $t->name }} ({{ $t->id }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </section>

    @if($tenantId)

    {{-- ═══════════ FLOW CONFIGURATION ═══════════ --}}
    <section class="cd-card cd-settings-card">
        <div class="cd-sc-head">
            @if(setting('crm_whatsapp_bot_vehicle_mode'))
            <div class="cd-sc-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff">
                <span class="material-icons">directions_car</span>
            </div>
            <div>
                <h3>Flujo de Conversación — Concesionario</h3>
                <p>Definí las preguntas que el bot hará al cliente antes de buscar vehículos con IA.</p>
            </div>
            @else
            <div class="cd-sc-icon cd-sc-accent">
                <span class="material-icons">smart_toy</span>
            </div>
            <div>
                <h3>Flujo de Conversación</h3>
                <p>Definí las preguntas que el bot hará al cliente paso a paso.</p>
            </div>
            @endif
        </div>

        @if(setting('crm_whatsapp_bot_vehicle_mode'))
        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:14px 16px;font-size:13px;color:#92400e;margin-bottom:16px">
            <span class="material-icons" style="font-size:16px;vertical-align:middle;margin-right:4px">directions_car</span>
            <strong>Modo Concesionario activo.</strong> El flujo recopila información del cliente y luego la IA busca vehículos en el API externo.
            Podés configurar en qué paso se activa la búsqueda IA usando la opción "Activar IA después de este paso" en cada opción del menú.
        </div>
        @endif

        {!! Form::open(['route' => 'crm.bot-flow.save', 'method' => 'POST']) !!}
        <input type="hidden" name="tenant_id" value="{{ $tenantId }}">

        <div class="row">
            <div class="col-md-8">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="greeting_message">Mensaje de bienvenida</label>
                    <textarea class="form-control" id="greeting_message" name="greeting_message" rows="3"
                        placeholder="{{ setting('crm_whatsapp_bot_vehicle_mode') ? 'Ej: ¡Hola! ¿Qué tipo de vehículo estás buscando?' : 'Ej: Hola, ¿Qué trámite desea realizar con nosotros?' }}">{{ $flow?->greeting_message ?? '' }}</textarea>
                    <small class="form-text text-muted">El primer mensaje que el bot enviará cuando un cliente inicie la conversación.</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label class="text-title-field">Estado</label>
                    <div class="mt-2">
                        <input type="hidden" name="is_active" value="0">
                        <label class="cd-toggle-label">
                            <input type="checkbox" name="is_active" value="1" {{ ($flow?->is_active ?? false) ? 'checked' : '' }}>
                            <span class="cd-toggle-switch"></span>
                            <span>Flujo activo</span>
                        </label>
                    </div>
                    <small class="form-text text-muted">
                        @if(setting('crm_whatsapp_bot_vehicle_mode'))
                            Si está desactivado, el bot usará IA directamente para buscar vehículos.
                        @else
                            Si está desactivado, el bot usará búsqueda con IA.
                        @endif
                    </small>
                </div>
            </div>
        </div>

        {{-- Flow Builder --}}
        <div class="cd-flow-builder" id="flowBuilder">
            <h4 style="font-size:14px;font-weight:600;color:var(--cd-ink-600);margin-bottom:12px">
                <span class="material-icons" style="font-size:18px;vertical-align:middle">account_tree</span>
                Opciones del menú principal
            </h4>

            <div id="optionsContainer"></div>

            <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addOption()">
                <span class="material-icons" style="font-size:16px;vertical-align:middle">add</span> Agregar opción
            </button>
        </div>

        <input type="hidden" name="flow_config" id="flowConfigInput">

        <div class="mt-4" style="display:flex;gap:10px;align-items:center">
            <button type="submit" class="cd-btn-primary" onclick="prepareFlowConfig()">
                <span class="material-icons" style="font-size:18px;vertical-align:middle">save</span> Guardar flujo
            </button>
            <button type="button" class="cd-btn-secondary" onclick="previewFlow()">
                <span class="material-icons" style="font-size:18px;vertical-align:middle">visibility</span> Vista previa
            </button>
        </div>

        {!! Form::close() !!}
    </section>

    {{-- ═══════════ MAIL CONFIGURATION ═══════════ --}}
    <section class="cd-card cd-settings-card">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-meta">
                <span class="material-icons">email</span>
            </div>
            <div>
                <h3>Configuración de Correo</h3>
                <p>Cuando el bot recopile toda la información, enviará un correo de notificación al agente.</p>
            </div>
        </div>

        {!! Form::open(['route' => 'crm.bot-flow.save-mail', 'method' => 'POST']) !!}
        <input type="hidden" name="tenant_id" value="{{ $tenantId }}">

        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="mail_host">Servidor SMTP</label>
                    <input type="text" class="form-control" id="mail_host" name="mail_host"
                        value="{{ $mailConfig->mail_host ?? 'smtp.gmail.com' }}" placeholder="smtp.gmail.com" />
                    <small class="form-text text-muted">Para Gmail: smtp.gmail.com</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="mail_port">Puerto</label>
                    <input type="number" class="form-control" id="mail_port" name="mail_port"
                        value="{{ $mailConfig->mail_port ?? 587 }}" />
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="mail_encryption">Encriptación</label>
                    <select class="form-control" id="mail_encryption" name="mail_encryption">
                        <option value="tls" {{ ($mailConfig->mail_encryption ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                        <option value="ssl" {{ ($mailConfig->mail_encryption ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                        <option value="none" {{ ($mailConfig->mail_encryption ?? '') === 'none' ? 'selected' : '' }}>Ninguna</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="mail_username">Usuario / Email</label>
                    <input type="email" class="form-control" id="mail_username" name="mail_username"
                        value="{{ $mailConfig->mail_username ?? '' }}" placeholder="tu-correo@gmail.com" />
                    <small class="form-text text-muted">Para Gmail usá una contraseña de aplicación (no la contraseña normal).</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="mail_password">Contraseña / App Password</label>
                    <input type="password" class="form-control" id="mail_password" name="mail_password"
                        value="" placeholder="{{ $mailConfig ? '••••••••' : '' }}" />
                    <small class="form-text text-muted">Dejá vacío para mantener la actual. Gmail requiere "Contraseña de aplicaciones".</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="mail_from_name">Nombre del remitente</label>
                    <input type="text" class="form-control" id="mail_from_name" name="mail_from_name"
                        value="{{ $mailConfig->mail_from_name ?? '' }}" placeholder="Bot WhatsApp" />
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="mail_from_address">Email remitente</label>
                    <input type="email" class="form-control" id="mail_from_address" name="mail_from_address"
                        value="{{ $mailConfig->mail_from_address ?? '' }}" placeholder="(usa el mismo usuario si vacío)" />
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="notification_emails">Correos de notificación</label>
                    <input type="text" class="form-control" id="notification_emails" name="notification_emails"
                        value="{{ $mailConfig ? implode(', ', $mailConfig->notification_emails ?? []) : '' }}"
                        placeholder="agente@email.com, admin@email.com" />
                    <small class="form-text text-muted">Correos donde se enviarán las notificaciones de nuevos leads. Separados por coma.</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label class="text-title-field">Estado</label>
                    <div class="mt-2">
                        <input type="hidden" name="is_active" value="0">
                        <label class="cd-toggle-label">
                            <input type="checkbox" name="is_active" value="1" {{ ($mailConfig->is_active ?? false) ? 'checked' : '' }}>
                            <span class="cd-toggle-switch"></span>
                            <span>Notificaciones activas</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="notification_whatsapp">
                        <span class="material-icons" style="font-size:16px;vertical-align:middle;color:#25d366">chat</span>
                        WhatsApp del agente (notificación)
                    </label>
                    <input type="text" class="form-control" id="notification_whatsapp" name="notification_whatsapp"
                        value="{{ $mailConfig->notification_whatsapp ?? '' }}"
                        placeholder="50688887777" />
                    <small class="form-text text-muted">
                        Número con código de país (ej: 50688887777). Cuando el bot complete un flujo, enviará un resumen por WhatsApp a este número.
                    </small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="whatsapp_template_name">
                        <span class="material-icons" style="font-size:16px;vertical-align:middle;color:#25d366">description</span>
                        Nombre del template (Meta)
                    </label>
                    <input type="text" class="form-control" id="whatsapp_template_name" name="whatsapp_template_name"
                        value="{{ $mailConfig->whatsapp_template_name ?? '' }}"
                        placeholder="lead_notification" />
                    <small class="form-text text-muted">
                        Nombre exacto del template aprobado en Meta Business Manager (solo minúsculas y guion bajo). Sin template, se envía texto libre — funciona solo si hay conversación activa.
                    </small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px 16px;font-size:13px;color:#166534;margin-bottom:16px">
                    <span class="material-icons" style="font-size:16px;vertical-align:middle;margin-right:4px">info</span>
                    <strong>Con template:</strong> la notificación llega siempre (~$0.014/msg).
                    <strong>Sin template:</strong> solo funciona si el agente chateó con el bot en las últimas 24h ($0).
                    El correo electrónico siempre se envía como respaldo.
                </div>
            </div>
        </div>

        <div class="mt-3" style="display:flex;gap:10px;align-items:center">
            <button type="submit" class="cd-btn-primary">
                <span class="material-icons" style="font-size:18px;vertical-align:middle">save</span> Guardar configuración
            </button>
            <button type="button" class="cd-btn-secondary" onclick="testMail()">
                <span class="material-icons" style="font-size:18px;vertical-align:middle">send</span> Enviar correo de prueba
            </button>
        </div>

        {!! Form::close() !!}
    </section>

    {{-- ═══════════ PREMIUM FEATURES ═══════════ --}}
    <section class="cd-card cd-settings-card">
        <div class="cd-sc-head">
            <div class="cd-sc-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff">
                <span class="material-icons">workspace_premium</span>
            </div>
            <div>
                <h3>Funcionalidades Premium</h3>
                <p>Activá o desactivá funcionalidades avanzadas para este tenant según su plan.</p>
            </div>
        </div>

        {!! Form::open(['route' => 'crm.bot-flow.save-features', 'method' => 'POST']) !!}
        <input type="hidden" name="tenant_id" value="{{ $tenantId }}">

        <div class="row">
            <div class="col-md-6">
                <div class="cd-premium-feature">
                    <div class="cd-pf-header">
                        <span class="material-icons cd-pf-icon" style="color:#3b82f6">photo_camera</span>
                        <div>
                            <strong>Fotos de propiedades</strong>
                            <small>Envía imágenes de propiedades por WhatsApp junto con los resultados de búsqueda.</small>
                        </div>
                    </div>
                    <div class="mt-2">
                        <input type="hidden" name="photos_enabled" value="0">
                        <label class="cd-toggle-label">
                            <input type="checkbox" name="photos_enabled" value="1" {{ ($features->photos_enabled ?? false) ? 'checked' : '' }}>
                            <span class="cd-toggle-switch"></span>
                            <span>Activar fotos</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="cd-premium-feature">
                    <div class="cd-pf-header">
                        <span class="material-icons cd-pf-icon" style="color:#8b5cf6">touch_app</span>
                        <div>
                            <strong>Botones interactivos</strong>
                            <small>Usa botones y listas de WhatsApp para menús y opciones en vez de texto plano.</small>
                        </div>
                    </div>
                    <div class="mt-2">
                        <input type="hidden" name="interactive_buttons_enabled" value="0">
                        <label class="cd-toggle-label">
                            <input type="checkbox" name="interactive_buttons_enabled" value="1" {{ ($features->interactive_buttons_enabled ?? false) ? 'checked' : '' }}>
                            <span class="cd-toggle-switch"></span>
                            <span>Activar botones</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-6">
                <div class="cd-premium-feature">
                    <div class="cd-pf-header">
                        <span class="material-icons cd-pf-icon" style="color:#10b981">dashboard</span>
                        <div>
                            <strong>Dashboard de conversaciones</strong>
                            <small>Panel con métricas de conversaciones, leads generados y actividad del bot.</small>
                        </div>
                    </div>
                    <div class="mt-2">
                        <input type="hidden" name="dashboard_enabled" value="0">
                        <label class="cd-toggle-label">
                            <input type="checkbox" name="dashboard_enabled" value="1" {{ ($features->dashboard_enabled ?? false) ? 'checked' : '' }}>
                            <span class="cd-toggle-switch"></span>
                            <span>Activar dashboard</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="cd-premium-feature">
                    <div class="cd-pf-header">
                        <span class="material-icons cd-pf-icon" style="color:#ef4444">picture_as_pdf</span>
                        <div>
                            <strong>PDFs y documentos</strong>
                            <small>Genera y envía fichas técnicas de propiedades como PDF por WhatsApp.</small>
                        </div>
                    </div>
                    <div class="mt-2">
                        <input type="hidden" name="pdf_documents_enabled" value="0">
                        <label class="cd-toggle-label">
                            <input type="checkbox" name="pdf_documents_enabled" value="1" {{ ($features->pdf_documents_enabled ?? false) ? 'checked' : '' }}>
                            <span class="cd-toggle-switch"></span>
                            <span>Activar PDFs</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="plan_name">Nombre del plan</label>
                    <select class="form-control" id="plan_name" name="plan_name">
                        <option value="">Sin plan</option>
                        <option value="basico" {{ ($features->plan_name ?? '') === 'basico' ? 'selected' : '' }}>Básico</option>
                        <option value="profesional" {{ ($features->plan_name ?? '') === 'profesional' ? 'selected' : '' }}>Profesional</option>
                        <option value="premium" {{ ($features->plan_name ?? '') === 'premium' ? 'selected' : '' }}>Premium</option>
                        <option value="enterprise" {{ ($features->plan_name ?? '') === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label class="text-title-field" for="plan_expires_at">Expiración del plan</label>
                    <input type="date" class="form-control" id="plan_expires_at" name="plan_expires_at"
                        value="{{ $features && $features->plan_expires_at ? $features->plan_expires_at->format('Y-m-d') : '' }}" />
                    <small class="form-text text-muted">Dejá vacío para acceso sin límite de tiempo.</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label class="text-title-field">Estado del plan</label>
                    <div class="mt-2">
                        @if($features && $features->plan_expires_at)
                            @if($features->plan_expires_at->isPast())
                                <span class="badge" style="background:#fef2f2;color:#991b1b;padding:6px 12px;border-radius:6px;font-size:13px">
                                    <span class="material-icons" style="font-size:14px;vertical-align:middle">warning</span> Expirado
                                </span>
                            @else
                                <span class="badge" style="background:#ecfdf5;color:#065f46;padding:6px 12px;border-radius:6px;font-size:13px">
                                    <span class="material-icons" style="font-size:14px;vertical-align:middle">check_circle</span>
                                    Activo — vence {{ $features->plan_expires_at->format('d/m/Y') }}
                                </span>
                            @endif
                        @elseif($features && ($features->photos_enabled || $features->interactive_buttons_enabled || $features->dashboard_enabled || $features->pdf_documents_enabled))
                            <span class="badge" style="background:#ecfdf5;color:#065f46;padding:6px 12px;border-radius:6px;font-size:13px">
                                <span class="material-icons" style="font-size:14px;vertical-align:middle">all_inclusive</span> Sin expiración
                            </span>
                        @else
                            <span class="badge" style="background:#f3f4f6;color:#6b7280;padding:6px 12px;border-radius:6px;font-size:13px">
                                Sin plan activo
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:14px 16px;font-size:13px;color:#92400e;margin-bottom:16px">
                    <span class="material-icons" style="font-size:16px;vertical-align:middle;margin-right:4px">info</span>
                    <strong>Nota:</strong> Las funcionalidades premium se desactivan automáticamente cuando el plan expira.
                    Si no se establece fecha de expiración, las funcionalidades permanecen activas indefinidamente.
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="cd-btn-primary">
                <span class="material-icons" style="font-size:18px;vertical-align:middle">save</span> Guardar funcionalidades
            </button>
        </div>

        {!! Form::close() !!}
    </section>

    @else
    <section class="cd-card cd-settings-card">
        <p style="text-align:center;color:var(--cd-ink-400);padding:40px">No hay tenants registrados.</p>
    </section>
    @endif

    {{-- ═══════════ PREVIEW MODAL ═══════════ --}}
    <div class="cd-modal-overlay" id="previewModal" style="display:none">
        <div class="cd-modal-content">
            <div class="cd-modal-header">
                <h3><span class="material-icons" style="vertical-align:middle">smartphone</span> Vista previa del flujo</h3>
                <button type="button" onclick="closePreview()" class="cd-modal-close">&times;</button>
            </div>
            <div class="cd-modal-body">
                <div class="cd-chat-preview" id="chatPreview"></div>
            </div>
        </div>
    </div>

</div>

@push('header')
<link rel="stylesheet" href="{{ asset('vendor/core/plugins/real-estate/css/crm-dashboard.css') }}">
<style>
.cd-flow-builder { background: var(--cd-soft-bg); border-radius: var(--cd-radius-sm); padding: 20px; margin-top: 16px; }
.cd-flow-option { background: var(--cd-card-bg); border: 1px solid var(--cd-line); border-radius: var(--cd-radius-sm); padding: 16px; margin-bottom: 12px; position: relative; }
.cd-flow-option-header { display: flex; gap: 10px; align-items: center; margin-bottom: 12px; }
.cd-flow-option-header input { flex: 1; }
.cd-flow-option .cd-remove-btn { position: absolute; top: 10px; right: 10px; background: none; border: none; color: #dc2626; cursor: pointer; padding: 4px; border-radius: 4px; }
.cd-flow-option .cd-remove-btn:hover { background: #fef2f2; }
.cd-flow-steps { margin-top: 10px; padding-left: 20px; border-left: 2px solid var(--cd-accent-tint); }
.cd-flow-step { background: #fafafa; border: 1px solid var(--cd-line-soft); border-radius: 8px; padding: 12px; margin-bottom: 8px; position: relative; }
.cd-flow-step .cd-remove-btn { position: absolute; top: 6px; right: 6px; font-size: 12px; }
.cd-flow-step-fields { display: grid; grid-template-columns: 1fr 120px 100px; gap: 8px; align-items: start; }
.cd-flow-step-fields input, .cd-flow-step-fields select { font-size: 13px; padding: 6px 10px; border: 1px solid var(--cd-line); border-radius: 6px; }
.cd-btn-primary { display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:var(--cd-accent);color:#fff;border:none;border-radius:var(--cd-radius-sm);font-weight:500;cursor:pointer;font-size:14px;transition:background .2s }
.cd-btn-primary:hover { background:var(--cd-accent-ink) }
.cd-btn-secondary { display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:var(--cd-soft-bg);color:var(--cd-ink-600);border:1px solid var(--cd-line);border-radius:var(--cd-radius-sm);font-weight:500;cursor:pointer;font-size:14px;transition:all .2s }
.cd-btn-secondary:hover { background:var(--cd-card-bg);border-color:var(--cd-ink-400) }
.cd-toggle-label { display: flex !important; align-items: center !important; gap: 10px !important; cursor: pointer !important; user-select: none; }
.cd-toggle-label input[type="checkbox"] { position: absolute !important; opacity: 0 !important; width: 0 !important; height: 0 !important; pointer-events: none !important; }
.cd-toggle-switch { display: inline-block !important; width: 44px !important; min-width: 44px !important; height: 24px !important; background: #d1d5db !important; border-radius: 12px !important; position: relative !important; transition: background .2s ease !important; flex-shrink: 0; vertical-align: middle; }
.cd-toggle-switch::after { content: '' !important; display: block !important; position: absolute !important; top: 3px !important; left: 3px !important; width: 18px !important; height: 18px !important; background: #fff !important; border-radius: 50% !important; transition: transform .2s ease !important; box-shadow: 0 1px 3px rgba(0,0,0,.2) !important; }
.cd-toggle-switch.is-on { background: var(--cd-accent, #717fe0) !important; }
.cd-toggle-switch.is-on::after { transform: translateX(20px) !important; }
.cd-toggle-label input[type="checkbox"]:checked + .cd-toggle-switch { background: var(--cd-accent, #717fe0) !important; }
.cd-toggle-label input[type="checkbox"]:checked + .cd-toggle-switch::after { transform: translateX(20px) !important; }
.cd-modal-overlay { position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:9999;display:flex;align-items:center;justify-content:center }
.cd-modal-content { background:white;border-radius:var(--cd-radius);width:90%;max-width:500px;max-height:80vh;overflow:hidden;display:flex;flex-direction:column }
.cd-modal-header { padding:16px 20px;border-bottom:1px solid var(--cd-line);display:flex;align-items:center;justify-content:space-between }
.cd-modal-header h3 { margin:0;font-size:16px }
.cd-modal-close { background:none;border:none;font-size:24px;cursor:pointer;color:var(--cd-ink-400) }
.cd-modal-body { padding:20px;overflow-y:auto;flex:1 }
.cd-chat-preview { display:flex;flex-direction:column;gap:8px }
.cd-chat-msg { padding:10px 14px;border-radius:12px;max-width:85%;font-size:13px;line-height:1.5;white-space:pre-line }
.cd-chat-bot { background:#e8f4e8;align-self:flex-start;border-bottom-left-radius:4px }
.cd-chat-user { background:#e3f2fd;align-self:flex-end;border-bottom-right-radius:4px }
.cd-premium-feature { background: var(--cd-soft-bg); border-radius: var(--cd-radius-sm); padding: 16px; border: 1px solid var(--cd-line-soft); }
.cd-pf-header { display: flex; gap: 12px; align-items: flex-start; margin-bottom: 8px; }
.cd-pf-header strong { display: block; font-size: 14px; font-weight: 600; color: var(--cd-ink-900); }
.cd-pf-header small { display: block; font-size: 12px; color: var(--cd-ink-400); margin-top: 2px; line-height: 1.4; }
.cd-pf-icon { font-size: 28px !important; flex-shrink: 0; }
</style>
@endpush

@push('footer')
<script>
let flowOptions = @json(($flow?->flow_config ?? ['options' => []])['options'] ?? []);
const currentTenantId = @json($tenantId);
const isVehicleMode = @json((bool) setting('crm_whatsapp_bot_vehicle_mode'));

function switchTenant(id) {
    window.location.href = '{{ route("crm.bot-flow.index") }}?tenant=' + id;
}

document.addEventListener('DOMContentLoaded', function() {
    renderOptions();
    initToggles();
});

function initToggles() {
    document.querySelectorAll('.cd-toggle-label').forEach(function(label) {
        const cb = label.querySelector('input[type="checkbox"]');
        const sw = label.querySelector('.cd-toggle-switch');
        if (!cb || !sw) return;
        if (cb.checked) sw.classList.add('is-on');
        else sw.classList.remove('is-on');

        function updateVisual() {
            if (cb.checked) sw.classList.add('is-on');
            else sw.classList.remove('is-on');
        }

        label.addEventListener('click', function(e) {
            e.preventDefault();
            cb.checked = !cb.checked;
            updateVisual();
        });
    });
}

function renderOptions() {
    const container = document.getElementById('optionsContainer');
    if (!container) return;
    container.innerHTML = '';
    flowOptions.forEach((opt, i) => {
        container.appendChild(createOptionEl(opt, i));
    });
}

function createOptionEl(opt, index) {
    const div = document.createElement('div');
    div.className = 'cd-flow-option';

    const labelPlaceholder = isVehicleMode ? 'Etiqueta (Buscar vehículo, Cotizar...)' : 'Etiqueta (Compra, Venta...)';
    const intentPlaceholder = isVehicleMode ? 'Intención (search, quote, test_drive)' : 'Intención (purchase, sale, rent)';

    let aiTriggerHtml = '';
    if (isVehicleMode) {
        const steps = opt.steps || [];
        const aiVal = opt.ai_after_step !== undefined && opt.ai_after_step !== null ? parseInt(opt.ai_after_step) : -1;
        let stepOpts = '<option value="-1"' + (aiVal === -1 ? ' selected' : '') + '>No activar IA</option>';
        steps.forEach((s, si) => {
            const label = s.question ? s.question.substring(0, 40) : ('Paso ' + (si + 1));
            stepOpts += '<option value="' + si + '"' + (aiVal === si ? ' selected' : '') + '>Después: ' + label + '</option>';
        });
        aiTriggerHtml = `
            <div class="cd-ai-trigger" style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;margin-top:10px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    <span class="material-icons" style="font-size:18px;color:#d97706">psychology</span>
                    <strong style="font-size:13px;color:#92400e">Activar búsqueda IA</strong>
                </div>
                <select class="form-control form-control-sm" onchange="flowOptions[${index}].ai_after_step = this.value === '-1' ? null : parseInt(this.value); renderOptions();" style="font-size:13px">
                    ${stepOpts}
                </select>
                <small style="font-size:11px;color:#92400e;margin-top:4px;display:block">
                    Cuando el cliente complete este paso, la IA buscará vehículos en el API usando las respuestas recopiladas.
                </small>
            </div>
        `;
    }

    div.innerHTML = `
        <button type="button" class="cd-remove-btn" onclick="removeOption(${index})" title="Eliminar opción">
            <span class="material-icons" style="font-size:18px">delete</span>
        </button>
        <div class="cd-flow-option-header">
            <input type="text" class="form-control form-control-sm" placeholder="Clave (1,2,3...)" value="${opt.key || ''}" style="max-width:60px"
                onchange="flowOptions[${index}].key = this.value">
            <input type="text" class="form-control form-control-sm" placeholder="${labelPlaceholder}" value="${opt.label || ''}"
                onchange="flowOptions[${index}].label = this.value">
            <input type="text" class="form-control form-control-sm" placeholder="${intentPlaceholder}" value="${opt.intent || ''}"
                onchange="flowOptions[${index}].intent = this.value">
        </div>
        <div class="form-group mb-2">
            <input type="text" class="form-control form-control-sm" placeholder="Mensaje de finalización" value="${opt.completion_message || ''}"
                onchange="flowOptions[${index}].completion_message = this.value">
            <small class="form-text text-muted">${isVehicleMode ? 'Mensaje cuando se completan todos los pasos (no aplica si IA se activa antes).' : 'Mensaje cuando se completan todos los pasos.'}</small>
        </div>
        <div class="cd-flow-steps" id="steps-${index}">
            <small style="font-weight:600;color:var(--cd-ink-600)">Pasos / Preguntas:</small>
            ${(opt.steps || []).map((step, si) => createStepHtml(index, si, step)).join('')}
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="addStep(${index})" style="font-size:12px">
            <span class="material-icons" style="font-size:14px;vertical-align:middle">add</span> Agregar paso
        </button>
        ${aiTriggerHtml}
    `;
    return div;
}

function createStepHtml(optIndex, stepIndex, step) {
    const opt = flowOptions[optIndex];
    const isAiStep = isVehicleMode && opt.ai_after_step !== undefined && opt.ai_after_step !== null && parseInt(opt.ai_after_step) === stepIndex;
    const aiStepStyle = isAiStep ? 'border:2px solid #f59e0b;background:#fffbeb;' : '';
    const aiStepBadge = isAiStep ? '<span style="font-size:11px;background:#f59e0b;color:#fff;padding:2px 8px;border-radius:10px;margin-left:8px">IA se activa aquí</span>' : '';

    const questionPlaceholder = isVehicleMode ? 'Pregunta (ej: ¿Qué marca le interesa?)' : 'Pregunta';
    const idPlaceholder = isVehicleMode ? 'ID (ej: brand, budget)' : 'ID campo';

    return `
        <div class="cd-flow-step" style="${aiStepStyle}">
            <button type="button" class="cd-remove-btn" onclick="removeStep(${optIndex}, ${stepIndex})" title="Eliminar paso">
                <span class="material-icons" style="font-size:14px">close</span>
            </button>
            <div class="cd-flow-step-fields">
                <input type="text" placeholder="${questionPlaceholder}" value="${step.question || ''}"
                    onchange="flowOptions[${optIndex}].steps[${stepIndex}].question = this.value">
                <input type="text" placeholder="${idPlaceholder}" value="${step.id || ''}"
                    onchange="flowOptions[${optIndex}].steps[${stepIndex}].id = this.value">
                <select onchange="flowOptions[${optIndex}].steps[${stepIndex}].type = this.value">
                    <option value="free_text" ${(step.type || 'free_text') === 'free_text' ? 'selected' : ''}>Texto libre</option>
                    <option value="yes_no" ${step.type === 'yes_no' ? 'selected' : ''}>Sí / No</option>
                    <option value="choice" ${step.type === 'choice' ? 'selected' : ''}>Opciones</option>
                    <option value="number" ${step.type === 'number' ? 'selected' : ''}>Número</option>
                </select>
            </div>
            ${aiStepBadge}
        </div>
    `;
}

function addOption() {
    flowOptions.push({ key: String(flowOptions.length + 1), label: '', intent: '', steps: [], completion_message: '' });
    renderOptions();
}

function removeOption(index) {
    if (confirm('¿Eliminar esta opción?')) {
        flowOptions.splice(index, 1);
        renderOptions();
    }
}

function addStep(optIndex) {
    if (!flowOptions[optIndex].steps) flowOptions[optIndex].steps = [];
    flowOptions[optIndex].steps.push({ id: '', question: '', type: 'free_text' });
    renderOptions();
}

function removeStep(optIndex, stepIndex) {
    flowOptions[optIndex].steps.splice(stepIndex, 1);
    renderOptions();
}

function prepareFlowConfig() {
    const cleaned = flowOptions.map(opt => {
        const o = { ...opt };
        if (o.ai_after_step === null || o.ai_after_step === undefined || o.ai_after_step === -1 || o.ai_after_step === '-1') {
            delete o.ai_after_step;
        } else {
            o.ai_after_step = parseInt(o.ai_after_step);
        }
        return o;
    });
    const config = { options: cleaned };
    document.getElementById('flowConfigInput').value = JSON.stringify(config);
}

function previewFlow() {
    const chat = document.getElementById('chatPreview');
    const defaultGreeting = isVehicleMode ? '¡Hola! ¿Qué tipo de vehículo estás buscando?' : '¡Hola! ¿Qué trámite desea realizar?';
    const greeting = document.getElementById('greeting_message').value || defaultGreeting;

    let html = `<div class="cd-chat-msg cd-chat-bot">${greeting}`;
    flowOptions.forEach(opt => {
        html += `\n${opt.key}. ${opt.label}`;
    });
    html += '</div>';

    if (flowOptions.length > 0) {
        const first = flowOptions[0];
        html += `<div class="cd-chat-msg cd-chat-user">${first.key}</div>`;

        const aiAfter = first.ai_after_step !== undefined && first.ai_after_step !== null ? parseInt(first.ai_after_step) : -1;

        if (first.steps && first.steps.length > 0) {
            for (let i = 0; i < first.steps.length; i++) {
                const step = first.steps[i];
                html += `<div class="cd-chat-msg cd-chat-bot">${step.question}</div>`;
                html += `<div class="cd-chat-msg cd-chat-user">[Respuesta del cliente]</div>`;

                if (isVehicleMode && aiAfter === i) {
                    html += `<div class="cd-chat-msg cd-chat-bot" style="background:#fef3c7;border:1px solid #fde68a">
                        <span style="font-size:12px;color:#92400e">🤖 <strong>IA se activa aquí</strong> — El bot busca vehículos usando las respuestas recopiladas...</span>
                    </div>`;
                    break;
                }
            }
        }

        if (!isVehicleMode || aiAfter === -1) {
            html += `<div class="cd-chat-msg cd-chat-bot">${first.completion_message || '¡Gracias! Un agente se comunicará pronto.'}</div>`;
        }
    }

    chat.innerHTML = html;
    document.getElementById('previewModal').style.display = 'flex';
}

function closePreview() {
    document.getElementById('previewModal').style.display = 'none';
}

function testMail() {
    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<span class="material-icons" style="font-size:18px;vertical-align:middle">hourglass_top</span> Enviando...';

    fetch('{{ route("crm.bot-flow.test-mail") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ tenant_id: currentTenantId })
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        btn.disabled = false;
        btn.innerHTML = '<span class="material-icons" style="font-size:18px;vertical-align:middle">send</span> Enviar correo de prueba';
    })
    .catch(err => {
        alert('Error: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<span class="material-icons" style="font-size:18px;vertical-align:middle">send</span> Enviar correo de prueba';
    });
}
</script>
@endpush
@endsection

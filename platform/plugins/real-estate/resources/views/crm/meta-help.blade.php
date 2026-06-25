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
            <a href="{{ route('crm.meta-settings') }}">Meta Integración</a>
            <span class="material-icons">chevron_right</span>
            <span class="cd-cur">Ayuda</span>
        </nav>
        <div class="cd-head-row">
            <div>
                <span class="cd-eyebrow">CRM · Guía de configuración</span>
                <h1>Configurar API de Meta</h1>
                <p class="cd-head-sub">Tutorial paso a paso para conectar Facebook, Instagram y WhatsApp con tu CRM.</p>
            </div>
            <div class="cd-leads-nav">
                <a class="cd-qbtn" href="{{ route('crm.meta-settings') }}" style="width:auto;padding:9px 16px">
                    <span class="cd-qi"><span class="material-icons">settings</span></span> Configuración
                </a>
                <a class="cd-qbtn" href="{{ route('crm.dashboard') }}" style="width:auto;padding:9px 16px">
                    <span class="cd-qi"><span class="material-icons">dashboard</span></span> Dashboard
                </a>
            </div>
        </div>
    </header>

    {{-- ═══════════ TOC ═══════════ --}}
    <section class="cd-card cd-settings-card">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-accent">
                <span class="material-icons">list</span>
            </div>
            <div>
                <h3>Contenido</h3>
                <p>Seleccioná la sección que necesitás configurar.</p>
            </div>
        </div>
        <div class="cd-help-toc">
            <a href="#paso1" class="cd-help-toc-item">
                <span class="cd-help-toc-num">1</span>
                <div>
                    <strong>Crear la App en Meta Developer</strong>
                    <small>Registrar una aplicación en Meta para Developers</small>
                </div>
            </a>
            <a href="#paso2" class="cd-help-toc-item">
                <span class="cd-help-toc-num">2</span>
                <div>
                    <strong>Configurar Webhooks</strong>
                    <small>Conectar tu CRM con Meta para recibir eventos</small>
                </div>
            </a>
            <a href="#paso3" class="cd-help-toc-item">
                <span class="cd-help-toc-num">3</span>
                <div>
                    <strong>Generar Page Access Token</strong>
                    <small>Token de larga duración para la Graph API</small>
                </div>
            </a>
            <a href="#paso4" class="cd-help-toc-item">
                <span class="cd-help-toc-num">4</span>
                <div>
                    <strong>Facebook Lead Ads</strong>
                    <small>Capturar leads desde formularios de anuncios</small>
                </div>
            </a>
            <a href="#paso5" class="cd-help-toc-item">
                <span class="cd-help-toc-num">5</span>
                <div>
                    <strong>WhatsApp Business API</strong>
                    <small>Recibir mensajes y activar el bot de IA</small>
                </div>
            </a>
            <a href="#paso6" class="cd-help-toc-item">
                <span class="cd-help-toc-num">6</span>
                <div>
                    <strong>Bot de WhatsApp con IA</strong>
                    <small>Configurar respuestas automáticas inteligentes</small>
                </div>
            </a>
            <a href="#paso7" class="cd-help-toc-item">
                <span class="cd-help-toc-num">7</span>
                <div>
                    <strong>Messenger e Instagram DM</strong>
                    <small>Capturar leads desde mensajes directos</small>
                </div>
            </a>
            <a href="#costos" class="cd-help-toc-item">
                <span class="cd-help-toc-num">$</span>
                <div>
                    <strong>Costos y precios</strong>
                    <small>Desglose de costos de WhatsApp API e IA</small>
                </div>
            </a>
        </div>
    </section>

    {{-- ═══════════ PASO 1: CREAR APP ═══════════ --}}
    <section class="cd-card cd-settings-card" id="paso1">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-meta">
                <span class="material-icons">add_circle</span>
            </div>
            <div>
                <h3>Paso 1: Crear la App en Meta Developer</h3>
                <p>Primero necesitás registrar una aplicación en la plataforma de Meta.</p>
            </div>
        </div>

        <ol class="cd-guide-steps">
            <li>
                <strong>Acceder a Meta for Developers</strong><br>
                Ingresá a <code>developers.facebook.com</code> con tu cuenta de Facebook (o la del cliente).<br>
                Si es la primera vez, aceptá los términos de desarrollador.
            </li>
            <li>
                <strong>Crear nueva App</strong><br>
                Hacé click en <strong>"My Apps"</strong> (arriba a la derecha) → <strong>"Create App"</strong>.<br>
                Seleccioná el tipo: <strong>"Business"</strong> (o "Other" si no aparece Business).<br>
                Ingresá un nombre descriptivo, ej: <code>CRM Inmobiliaria MiEmpresa</code>.
            </li>
            <li>
                <strong>Seleccionar Business Portfolio</strong><br>
                Si tenés un Meta Business Manager, seleccionalo. Si no, podés crear uno después.<br>
                Click en <strong>"Create App"</strong>.
            </li>
            <li>
                <strong>Copiar credenciales</strong><br>
                Una vez creada la app, andá a <strong>"Settings" → "Basic"</strong>:<br>
                • <strong>App ID:</strong> Copialo y pegalo en la configuración de Meta del CRM (campo "App ID").<br>
                • <strong>App Secret:</strong> Click en "Show" → Copialo → Pegalo en "App Secret" del CRM.<br>
                ⚠️ <em>El App Secret es privado — nunca lo compartas públicamente.</em>
            </li>
            <li>
                <strong>Agregar productos a la App</strong><br>
                En el menú lateral, hacé click en <strong>"Add Product"</strong> y agregá los que necesités:<br>
                • <strong>Webhooks</strong> — Obligatorio para todos los canales<br>
                • <strong>WhatsApp</strong> — Si vas a usar WhatsApp Business API<br>
                • <strong>Messenger</strong> — Si vas a capturar mensajes de Facebook Messenger<br>
                • <strong>Instagram</strong> — Si vas a capturar DMs de Instagram
            </li>
        </ol>
    </section>

    {{-- ═══════════ PASO 2: WEBHOOKS ═══════════ --}}
    <section class="cd-card cd-settings-card" id="paso2">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-accent">
                <span class="material-icons">webhook</span>
            </div>
            <div>
                <h3>Paso 2: Configurar Webhooks</h3>
                <p>Los webhooks permiten que Meta envíe eventos (leads, mensajes) a tu CRM en tiempo real.</p>
            </div>
        </div>

        <ol class="cd-guide-steps">
            <li>
                <strong>Ir a Webhooks en la App</strong><br>
                En el panel de tu App, hacé click en <strong>"Webhooks"</strong> en el menú lateral.
            </li>
            <li>
                <strong>Seleccionar tipo de objeto</strong><br>
                En el dropdown "Select an object", seleccioná <strong>"Page"</strong>.
            </li>
            <li>
                <strong>Subscribir Callback URL</strong><br>
                Click en <strong>"Subscribe to this object"</strong> (o "Edit" si ya existe).<br>
                Completá los campos:<br><br>
                <div class="cd-webhook-url-box" style="margin: 12px 0">
                    <input type="text" readonly value="{{ url('/webhook/meta') }}" id="helpWebhookUrl" />
                    <button type="button" class="cd-copy-btn" onclick="navigator.clipboard.writeText(document.getElementById('helpWebhookUrl').value);Botble.showSuccess('Copiado')">
                        <span class="material-icons" style="font-size:16px">content_copy</span> Copiar
                    </button>
                </div>
                <strong>Verify Token:</strong> <code>{{ setting('crm_meta_verify_token') }}</code><br><br>
                Click en <strong>"Verify and Save"</strong>. Si todo está bien, Meta verificará la URL y la guardará.<br>
                ⚠️ <em>Si da error: verificá que tu dominio tenga SSL (https) y que la URL sea accesible públicamente.</em>
            </li>
            <li>
                <strong>Subscribir a eventos</strong><br>
                Una vez verificado, activá los eventos necesarios:<br>
                • <strong>leadgen</strong> — Para capturar leads de Facebook/Instagram Lead Ads<br>
                • <strong>messages</strong> — Para capturar mensajes de Messenger<br>
                • <strong>feed</strong> — Opcional, para comentarios en publicaciones<br><br>
                Marcá la casilla "Subscribe" de cada uno.
            </li>
            <li>
                <strong>Subscribir la Página</strong><br>
                ⚠️ <strong>Paso crítico</strong> — Solo configurar webhooks NO es suficiente. Debés subscribir tu página específica:<br><br>
                Andá a <strong>"Facebook Login" → "Settings"</strong> en tu app, o usá el <strong>Graph API Explorer</strong>:<br>
                <code>POST /{page-id}/subscribed_apps?subscribed_fields=leadgen,messages&access_token={PAGE_TOKEN}</code><br><br>
                También podés hacerlo desde <strong>"Webhooks" → "Page" → Click en "Subscribe"</strong> al lado de tu página.
            </li>
        </ol>
    </section>

    {{-- ═══════════ PASO 3: PAGE TOKEN ═══════════ --}}
    <section class="cd-card cd-settings-card" id="paso3">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-green">
                <span class="material-icons">key</span>
            </div>
            <div>
                <h3>Paso 3: Generar Page Access Token (Long-lived)</h3>
                <p>El token permite al CRM comunicarse con la Graph API de Meta para obtener datos de leads.</p>
            </div>
        </div>

        <ol class="cd-guide-steps">
            <li>
                <strong>Abrir Graph API Explorer</strong><br>
                Andá a <code>developers.facebook.com/tools/explorer/</code><br>
                Seleccioná tu App en el dropdown superior.
            </li>
            <li>
                <strong>Seleccionar permisos</strong><br>
                Hacé click en <strong>"Add Permission"</strong> y agregá:<br>
                • <code>pages_manage_metadata</code> — Para recibir webhooks de la página<br>
                • <code>pages_read_engagement</code> — Para leer interacciones<br>
                • <code>leads_retrieval</code> — Para obtener datos de Lead Ads<br>
                • <code>pages_messaging</code> — Para Messenger (si aplica)<br>
                • <code>pages_manage_ads</code> — Para administrar Lead Ads
            </li>
            <li>
                <strong>Generar token de usuario</strong><br>
                Click en <strong>"Generate Access Token"</strong>.<br>
                Se abrirá un popup pidiendo permisos — aceptá todos.<br>
                Copiá el token generado (empieza con <code>EAA...</code>).
            </li>
            <li>
                <strong>Seleccionar la Página</strong><br>
                En el dropdown de "User or Page", cambiá a tu <strong>Página de Facebook</strong>.<br>
                Click en <strong>"Generate Access Token"</strong> nuevamente.<br>
                Este es el <strong>Page Token</strong> — copialo.
            </li>
            <li>
                <strong>Extender a Long-lived Token</strong><br>
                El token generado dura ~1 hora. Para extenderlo:<br><br>
                Andá a <strong>"Access Token Debugger"</strong>: <code>developers.facebook.com/tools/debug/accesstoken/</code><br>
                Pegá el token → Click en "Debug" → Click en <strong>"Extend Access Token"</strong>.<br><br>
                El nuevo token dura ~60 días. Copialo y pegalo en el campo <strong>"Page Access Token"</strong> del CRM.<br><br>
                ⚠️ <em>Recordá renovar el token cada ~60 días. Si expira, los Lead Ads y el bot dejarán de funcionar.</em>
            </li>
            <li>
                <strong>Token permanente (opcional)</strong><br>
                Para un token que no expire, usá el Graph API Explorer:<br>
                <code>GET /me/accounts?access_token={LONG_LIVED_USER_TOKEN}</code><br>
                El <code>access_token</code> de la página en la respuesta es <strong>permanente</strong> — no expira nunca.<br>
                Copialo y usalo como tu Page Access Token.
            </li>
        </ol>
    </section>

    {{-- ═══════════ PASO 4: LEAD ADS ═══════════ --}}
    <section class="cd-card cd-settings-card" id="paso4">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-meta">
                <span class="material-icons">contact_page</span>
            </div>
            <div>
                <h3>Paso 4: Facebook / Instagram Lead Ads</h3>
                <p>Capturá automáticamente los datos de quienes completan formularios en tus anuncios.</p>
            </div>
        </div>

        <ol class="cd-guide-steps">
            <li>
                <strong>Crear un anuncio con Lead Form</strong><br>
                En <strong>Meta Ads Manager</strong> (<code>adsmanager.facebook.com</code>):<br>
                • Creá una campaña con objetivo <strong>"Leads"</strong><br>
                • En el Ad Set, seleccioná <strong>"Instant Form"</strong> como método de captación<br>
                • Diseñá tu formulario con los campos que necesités (nombre, email, teléfono, etc.)
            </li>
            <li>
                <strong>Verificar webhook de leadgen</strong><br>
                Asegurate de que el evento <code>leadgen</code> esté subscrito en los Webhooks de tu App (Paso 2).<br>
                Y que tu Página esté subscrita a la App.
            </li>
            <li>
                <strong>Probar con Lead Ads Testing Tool</strong><br>
                Andá a <code>developers.facebook.com/tools/lead-ads-testing</code><br>
                Seleccioná tu Página → Seleccioná tu formulario → Click en <strong>"Create Lead"</strong>.<br><br>
                Esto genera un lead de prueba. Verificá que aparezca en tu CRM (sección Leads, fuente "Facebook Lead Ad").
            </li>
            <li>
                <strong>¿Qué datos se capturan?</strong><br>
                El CRM extrae automáticamente los siguientes campos del formulario:<br>
                • <strong>Nombre:</strong> full_name, nombre_completo, first_name, nombre<br>
                • <strong>Email:</strong> email, correo, correo_electronico<br>
                • <strong>Teléfono:</strong> phone_number, telefono, celular<br>
                • <strong>Otros campos:</strong> Se guardan en las notas del lead<br><br>
                Los campos se detectan automáticamente en español e inglés.
            </li>
        </ol>
    </section>

    {{-- ═══════════ PASO 5: WHATSAPP ═══════════ --}}
    <section class="cd-card cd-settings-card" id="paso5">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-green">
                <i class="fab fa-whatsapp" style="font-size:22px"></i>
            </div>
            <div>
                <h3>Paso 5: WhatsApp Business API</h3>
                <p>Recibí mensajes de WhatsApp y creá leads automáticamente.</p>
            </div>
        </div>

        <ol class="cd-guide-steps">
            <li>
                <strong>Agregar WhatsApp a tu App</strong><br>
                En tu App de Meta Developer, andá a <strong>"Add Product"</strong> → <strong>"WhatsApp"</strong> → <strong>"Set Up"</strong>.
            </li>
            <li>
                <strong>Configurar número de teléfono</strong><br>
                En <strong>"WhatsApp" → "Getting Started"</strong>:<br>
                • Meta te da un número de prueba gratuito. Para producción, agregá tu propio número.<br>
                • Click en <strong>"Add Phone Number"</strong> → Verificá con código SMS o llamada.<br>
                • Copiá el <strong>Phone Number ID</strong> (se muestra debajo del número) → Pegalo en el CRM.
            </li>
            <li>
                <strong>Copiar Business Account ID</strong><br>
                En la misma sección, copiá el <strong>"WhatsApp Business Account ID"</strong> → Pegalo en el CRM.
            </li>
            <li>
                <strong>Configurar webhook de WhatsApp</strong><br>
                En <strong>"WhatsApp" → "Configuration"</strong>:<br>
                • <strong>Callback URL:</strong> <code>{{ url('/webhook/meta') }}</code> (la misma del Paso 2)<br>
                • <strong>Verify Token:</strong> <code>{{ setting('crm_meta_verify_token') }}</code><br>
                • Click en "Verify and Save"<br>
                • En "Webhook fields", subscribí a: <strong>messages</strong>
            </li>
            <li>
                <strong>Generar token permanente de WhatsApp</strong><br>
                En <strong>"WhatsApp" → "Getting Started"</strong>, hay un "Temporary access token" que dura 24h.<br>
                Para un token permanente:<br>
                • Andá a <strong>"Business Settings" → "System Users"</strong> en business.facebook.com<br>
                • Creá un System User con rol Admin<br>
                • Asignale los permisos: <code>whatsapp_business_messaging</code>, <code>whatsapp_business_management</code><br>
                • Generá un token → Este es permanente<br>
                • Pegalo como <strong>Page Access Token</strong> en el CRM (se usa el mismo campo).
            </li>
            <li>
                <strong>Probar</strong><br>
                Enviá un mensaje al número de WhatsApp Business desde tu celular.<br>
                Verificá que el lead aparezca en el CRM con fuente "WhatsApp".
            </li>
        </ol>

        <div style="background:var(--cd-accent-tint);border-radius:var(--cd-radius-sm);padding:16px 20px;margin-top:16px">
            <strong style="color:var(--cd-accent)">💡 Nota sobre costos:</strong>
            <p style="margin:8px 0 0;font-size:.88rem;color:var(--cd-ink-600)">
                Los mensajes de servicio (donde el cliente escribe primero y vos respondés) son <strong>100% gratis</strong> en WhatsApp Business API.
                Solo se cobra por mensajes de marketing que vos iniciás. Para un bot que responde consultas, el costo de WhatsApp es $0.
            </p>
        </div>
    </section>

    {{-- ═══════════ PASO 6: BOT IA ═══════════ --}}
    <section class="cd-card cd-settings-card" id="paso6">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-accent">
                <span class="material-icons">smart_toy</span>
            </div>
            <div>
                <h3>Paso 6: Bot de WhatsApp con IA</h3>
                <p>Configurá respuestas automáticas inteligentes que buscan propiedades en tu sistema.</p>
            </div>
        </div>

        <ol class="cd-guide-steps">
            <li>
                <strong>Obtener API Key del proveedor de IA</strong><br><br>
                <strong>Opción A — Claude (Anthropic) — Recomendado:</strong><br>
                • Registrate en <code>console.anthropic.com</code><br>
                • Andá a <strong>"API Keys"</strong> → <strong>"Create Key"</strong><br>
                • Copiá la key (empieza con <code>sk-ant-...</code>)<br>
                • Modelo recomendado: <code>claude-haiku-4-5-20251001</code> (~$0.004 por conversación)<br><br>
                <strong>Opción B — OpenAI (GPT):</strong><br>
                • Registrate en <code>platform.openai.com</code><br>
                • Andá a <strong>"API Keys"</strong> → <strong>"Create new secret key"</strong><br>
                • Copiá la key (empieza con <code>sk-...</code>)<br>
                • Modelo recomendado: <code>gpt-4o-mini</code> (~$0.0005 por conversación)
            </li>
            <li>
                <strong>Configurar en el CRM</strong><br>
                Andá a <strong>CRM → Meta Integración</strong> → Sección "Bot de WhatsApp con IA":<br>
                • Activá el switch <strong>"Activar Bot IA"</strong><br>
                • Seleccioná el proveedor (Claude o OpenAI)<br>
                • Pegá la API Key<br>
                • Dejá el modelo por defecto o cambialo<br>
                • Click en <strong>"Guardar configuración"</strong>
            </li>
            <li>
                <strong>Personalizar el bot (opcional)</strong><br>
                Podés personalizar el <strong>System Prompt</strong> para cambiar la personalidad del bot:<br>
                • Idioma y tono de las respuestas<br>
                • Nombre de la inmobiliaria<br>
                • Horarios de atención<br>
                • Información de contacto del agente<br><br>
                Si lo dejás vacío, usa un prompt profesional por defecto en español.
            </li>
            <li>
                <strong>Probar el bot</strong><br>
                • Enviá un mensaje al número de WhatsApp Business: <em>"¿Tienen casas en venta?"</em><br>
                • El bot debería responder con propiedades de tu sistema<br>
                • Probá con diferentes consultas: por zona, precio, habitaciones, tipo<br>
                • Verificá que el lead se cree en el CRM
            </li>
            <li>
                <strong>¿Cómo funciona?</strong><br>
                1. El cliente envía un mensaje por WhatsApp<br>
                2. El CRM recibe el mensaje via webhook<br>
                3. La IA analiza qué está buscando el cliente<br>
                4. Busca propiedades en tu base de datos que coincidan<br>
                5. Genera una respuesta amigable con las propiedades encontradas<br>
                6. Envía la respuesta por WhatsApp<br>
                7. Crea/actualiza el lead en el CRM automáticamente
            </li>
        </ol>
    </section>

    {{-- ═══════════ PASO 7: MESSENGER / INSTAGRAM ═══════════ --}}
    <section class="cd-card cd-settings-card" id="paso7">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-meta">
                <span class="material-icons">forum</span>
            </div>
            <div>
                <h3>Paso 7: Messenger e Instagram DM</h3>
                <p>Capturá leads desde mensajes directos en Facebook Messenger e Instagram.</p>
            </div>
        </div>

        <ol class="cd-guide-steps">
            <li>
                <strong>Messenger — Agregar producto</strong><br>
                En tu App de Meta Developer: <strong>"Add Product"</strong> → <strong>"Messenger"</strong> → <strong>"Set Up"</strong>.<br>
                En <strong>"Messenger" → "Settings"</strong>:<br>
                • Conectá tu Página de Facebook<br>
                • Generá un token de página<br>
                • El webhook ya está configurado (mismo del Paso 2) — verificá que <code>messages</code> esté subscrito
            </li>
            <li>
                <strong>Instagram DM — Agregar producto</strong><br>
                En tu App: <strong>"Add Product"</strong> → <strong>"Instagram"</strong> → <strong>"Set Up"</strong>.<br>
                Requisitos:<br>
                • La cuenta de Instagram debe ser <strong>Business</strong> o <strong>Creator</strong><br>
                • Debe estar conectada a una Página de Facebook<br>
                • Necesitás el permiso <code>instagram_manage_messages</code>
            </li>
            <li>
                <strong>Permisos necesarios</strong><br>
                Para ambos canales, asegurate de tener estos permisos en tu App:<br>
                • <code>pages_messaging</code> — Messenger<br>
                • <code>instagram_manage_messages</code> — Instagram DM<br>
                • <code>pages_manage_metadata</code> — Webhooks de la página
            </li>
            <li>
                <strong>App Review (para producción)</strong><br>
                Para que Messenger e Instagram funcionen con usuarios que no son admins/testers de tu App:<br>
                • Andá a <strong>"App Review" → "Permissions and Features"</strong><br>
                • Solicitá revisión para <code>pages_messaging</code> y <code>instagram_manage_messages</code><br>
                • Meta revisará tu app en 1-5 días<br><br>
                ⚠️ <em>Mientras la app esté en "Development mode", solo funciona con admins y testers agregados manualmente.</em>
            </li>
        </ol>
    </section>

    {{-- ═══════════ COSTOS ═══════════ --}}
    <section class="cd-card cd-settings-card" id="costos">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-accent">
                <span class="material-icons">payments</span>
            </div>
            <div>
                <h3>Costos y precios</h3>
                <p>Desglose de costos para operar la integración de Meta con IA.</p>
            </div>
        </div>

        <div style="margin-bottom:24px">
            <h4 style="font-family:'Playfair Display',serif;font-size:1rem;margin-bottom:12px">WhatsApp Business API</h4>
            <table class="cd-logs-table">
                <thead>
                    <tr>
                        <th>Tipo de mensaje</th>
                        <th>Costo (Costa Rica / Latinoamérica)</th>
                        <th>Cuándo aplica</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong style="color:#25D366">Service (cliente inicia)</strong></td>
                        <td><strong style="color:#25D366">GRATIS</strong></td>
                        <td>Respuestas dentro de 24h — ilimitadas</td>
                    </tr>
                    <tr>
                        <td>Marketing (templates)</td>
                        <td>$0.0860 / mensaje</td>
                        <td>Promociones, anuncios que vos iniciás</td>
                    </tr>
                    <tr>
                        <td>Utility (templates)</td>
                        <td>$0.0140 / mensaje</td>
                        <td>Confirmaciones, actualizaciones</td>
                    </tr>
                    <tr>
                        <td>Authentication</td>
                        <td>$0.0140 / mensaje</td>
                        <td>OTP, verificaciones</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="margin-bottom:24px">
            <h4 style="font-family:'Playfair Display',serif;font-size:1rem;margin-bottom:12px">Inteligencia Artificial (por conversación)</h4>
            <table class="cd-logs-table">
                <thead>
                    <tr>
                        <th>Modelo</th>
                        <th>Costo / conversación</th>
                        <th>Costo / 500 conv. al mes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Claude Haiku 4.5</strong> (Recomendado)</td>
                        <td>~$0.004 (0.4¢)</td>
                        <td>~$2.00 / mes</td>
                    </tr>
                    <tr>
                        <td>GPT-4o-mini</td>
                        <td>~$0.0005 (0.05¢)</td>
                        <td>~$0.25 / mes</td>
                    </tr>
                    <tr>
                        <td>Claude Sonnet 4.6</td>
                        <td>~$0.012 (1.2¢)</td>
                        <td>~$6.00 / mes</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="background:var(--cd-accent-tint);border-radius:var(--cd-radius-sm);padding:16px 20px">
            <strong style="color:var(--cd-accent)">💡 Resumen:</strong>
            <p style="margin:8px 0 0;font-size:.88rem;color:var(--cd-ink-600)">
                Para un bot que responde consultas de clientes (service conversations), el costo total es
                <strong>prácticamente $0</strong>: WhatsApp es gratis + la IA cuesta ~$2/mes por 500 conversaciones.
                Solo pagarías por mensajes de marketing que vos iniciés proactivamente.
            </p>
        </div>
    </section>

    {{-- ═══════════ TROUBLESHOOTING ═══════════ --}}
    <section class="cd-card cd-settings-card">
        <div class="cd-sc-head">
            <div class="cd-sc-icon cd-sc-accent">
                <span class="material-icons">help_outline</span>
            </div>
            <div>
                <h3>Solución de problemas</h3>
                <p>Problemas comunes y cómo resolverlos.</p>
            </div>
        </div>

        <div style="font-size:.88rem;line-height:1.7;color:var(--cd-ink-600)">
            <div style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--cd-line-soft)">
                <strong style="color:var(--cd-ink-900)">❌ "Webhook verification failed"</strong><br>
                • Verificá que la URL <code>{{ url('/webhook/meta') }}</code> sea accesible públicamente (https)<br>
                • Verificá que el Verify Token coincida exactamente con el configurado en el CRM<br>
                • Si usás Cloudflare, desactivá "Under Attack Mode" temporalmente
            </div>
            <div style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--cd-line-soft)">
                <strong style="color:var(--cd-ink-900)">❌ Leads no aparecen en el CRM</strong><br>
                • Revisá los <strong>Webhook Logs</strong> en la página de configuración de Meta<br>
                • Verificá que el "Auto-importar leads" esté activado<br>
                • Verificá que la Página esté subscrita a la App (<code>POST /{page-id}/subscribed_apps</code>)<br>
                • Probá con el Lead Ads Testing Tool de Meta
            </div>
            <div style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--cd-line-soft)">
                <strong style="color:var(--cd-ink-900)">❌ Bot de WhatsApp no responde</strong><br>
                • Verificá que el bot esté activado en la configuración<br>
                • Verificá que la API Key del LLM sea correcta<br>
                • Verificá que el Phone Number ID y el Page Access Token estén configurados<br>
                • Revisá los logs del sistema: <code>storage/logs/laravel.log</code>
            </div>
            <div>
                <strong style="color:var(--cd-ink-900)">❌ Token expirado</strong><br>
                • Los tokens de página expiran cada ~60 días<br>
                • Para un token permanente, seguí el Paso 3.6 (token vía System User)<br>
                • Si el bot deja de funcionar repentinamente, lo más probable es que el token expiró
            </div>
        </div>
    </section>

</div>
@endsection

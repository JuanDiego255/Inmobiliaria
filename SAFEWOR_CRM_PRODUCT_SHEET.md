# SafeWor Solutions — CRM Inmobiliario & Automotriz

**Sistema integral de gestión inmobiliaria y automotriz con CRM avanzado, bot de WhatsApp con IA y arquitectura multi-tenant.**

---

## Descripcion General

Plataforma web completa para empresas inmobiliarias y concesionarios de vehiculos. Permite gestionar propiedades/vehiculos, capturar leads automaticamente desde redes sociales, administrar el pipeline de ventas y atender clientes via WhatsApp con un bot potenciado por inteligencia artificial.

Arquitectura multi-tenant: un solo despliegue sirve a multiples empresas, cada una con su base de datos, dominio y configuracion independiente.

---

## Modulos y Funcionalidades

### Gestion de Propiedades

- Crear, editar y eliminar propiedades (casas, apartamentos, terrenos, locales, oficinas)
- Galeria de imagenes con multiples fotos por propiedad
- Campos detallados: precio, ubicacion, habitaciones, banos, area (m2), parqueo
- Clasificacion por tipo (venta/alquiler), categoria y proyecto
- Propiedades destacadas con prioridad en listados
- Campos personalizados (custom fields) configurables por el admin
- Facilidades y amenidades asociadas (piscina, gimnasio, seguridad, etc.)
- Vista de grilla con filtros avanzados en el admin
- Duplicar propiedades para crear variaciones rapidas
- Generacion de PDF "Ficha Tecnica" por propiedad (DomPDF): imagen, specs, descripcion, amenidades
- Importacion masiva desde Excel/CSV (bulk import) con template descargable
- Exportacion de propiedades a Excel

### Tours Virtuales 360

- Pagina publica dedicada a tours virtuales
- Integracion con plataformas de tours 360 via URL embebida
- API para listar tours disponibles (AJAX)
- Filtro y busqueda de propiedades con tour virtual

### Proyectos Inmobiliarios

- Gestionar desarrollos/proyectos con multiples propiedades
- Importacion masiva de proyectos desde Excel
- Exportacion de proyectos

### Gestion de Vehiculos (Modo Concesionario)

- Modo dual: el sistema alterna entre inmobiliario y automotriz
- Conexion con API externa de vehiculos (REST API con Bearer token)
- Busqueda por marca, modelo, ano, color, transmision, combustible, condicion, precio
- Detalle de vehiculo con fotos, especificaciones y tour virtual 360

---

### CRM (Customer Relationship Management)

#### Dashboard

- Panel con KPIs en tiempo real: leads nuevos, contactados, en negociacion, ganados, perdidos
- Tareas del dia para el agente logueado
- Leads recientes con acceso rapido
- Timeline de actividades
- Acciones rapidas (nuevo lead, nueva tarea, nuevo recordatorio)
- Resumen visual del pipeline (embudo de ventas con graficos ApexCharts)
- Embudo de conversion con porcentajes por etapa
- Rendimiento por agente: leads totales, ganados, perdidos, tasa de cierre, score promedio

#### Leads

- Captura automatica desde Facebook Lead Ads, Instagram DMs, Messenger y WhatsApp
- 11 fuentes de lead: WhatsApp, Facebook Lead Ads, Instagram DM, Messenger, sitio web, consultas, referido, redes sociales, telefono, manual, otro
- Tabla completa con filtros, busqueda y paginacion
- Detalle de lead con historial completo (actividades, notas, propiedades vistas)
- Asignacion a agentes (manual o automatica al agente con menos carga)
- Deteccion de duplicados automatica (por email o telefono) antes de crear
- Exportacion de leads a Excel

#### Lead Scoring Automatico

- Puntuacion automatica de 0 a 100 basada en:
  - Completitud de datos (+15 por email, +15 por telefono, +10 por presupuesto)
  - Fuente del lead (referido: +20, sitio web: +15, redes sociales: +10)
  - Cantidad de actividades registradas (hasta +15)
  - Propiedades asociadas (hasta +10)
- Recalculo masivo disponible desde el admin

#### Automatizacion por Etapa

Al cambiar un lead de etapa, el sistema automaticamente:
- Registra la actividad del cambio
- Crea tareas de seguimiento con prioridad y fecha limite:
  - Contactado: "Calificar lead" (2 dias, prioridad media)
  - Calificado: "Preparar propuesta comercial" (3 dias, prioridad alta)
  - En negociacion: "Seguimiento de negociacion" (5 dias, prioridad alta)
  - Ganado: "Cerrar documentacion" (7 dias, prioridad urgente)

#### Pipeline de Ventas (Kanban)

- Vista Kanban drag & drop con 6 etapas: Nuevo, Contactado, Calificado, En Negociacion, Ganado, Perdido
- Arrastrar leads entre etapas para actualizar estado
- Cards de lead con info clave (nombre, telefono, fuente, fecha, score)
- Crear leads directamente desde el pipeline

#### Tareas

- Crear, editar y completar tareas
- Asignar a agentes especificos
- Prioridades: baja, media, alta, urgente
- Estados: pendiente, en progreso, completada, cancelada
- Filtros por estado, prioridad y agente
- Vinculacion con leads

#### Recordatorios

- Sistema de alertas y recordatorios
- Filtro por estado (pendiente/visto) y lead asociado
- Notificaciones visuales en el admin

#### Actividades

- Registro de llamadas, emails, reuniones, visitas y notas
- Timeline cronologica por lead
- Formulario modal para crear actividades rapidas

#### Calendario

- Vista unificada de tareas, recordatorios, actividades y cierres de leads
- Integracion con Google Calendar (OAuth, bidireccional)
- Filtros por tipo de evento (tareas, recordatorios, actividades, cierres, Google Calendar)
- Crear eventos directamente desde el calendario

#### Configuracion del CRM

- Conexion con Google Calendar (OAuth)
- Configuracion de parametros generales del CRM

---

### Tableros de Propiedades (Boards)

- Crear tableros personalizados para agrupar propiedades
- Asociar propiedades a tableros desde la tabla de propiedades
- Tablero publico compartible con clientes (link unico sin login)
- Vista Kanban con columnas: Propiedades, Interesado, Visitado, Descartado
- Sistema de calificacion/rating por propiedad dentro del tablero
- Vinculacion con clientes y leads del CRM
- Links para compartir tableros

---

### Sitio Web Publico (Tema Flex Home)

- Pagina principal configurable con shortcodes (buscador, propiedades destacadas, carrusel, proyectos, agentes, noticias)
- Buscador con filtros: ubicacion (estado/ciudad cascading), tipo (venta/alquiler), categoria, precio, habitaciones, banos
- Carrusel de propiedades con scroll horizontal, filtrado en cliente y contador de progreso
- Mapa interactivo con propiedades geolocalizadas (Leaflet.js / OpenStreetMap)
- Listado de propiedades con filtros, paginacion y ordenamiento (precio, fecha, nombre)
- Detalle de propiedad: galeria mosaico con lightbox fullscreen, ficha tecnica, amenidades, facilidades con distancia, ubicacion en mapa, video embebido, tour virtual 360, compartir en redes, comentarios de Facebook, resenas con estrellas, agente de contacto con boton de WhatsApp, propiedades similares
- Listado de proyectos inmobiliarios con carrusel y filtros
- Pagina dedicada de tours virtuales 360 con busqueda
- Perfiles publicos de agentes con sus propiedades, biografia y estadisticas
- Formulario de contacto con mapa de oficina, horario y redes sociales
- Pagina de carreras/empleos (ofertas de trabajo)
- Blog / noticias integrado
- Paginas estaticas (Acerca de, Terminos, Politicas)
- Multi-idioma con selector de idioma y soporte RTL (derecha-a-izquierda)
- Multi-moneda configurable
- Diseno responsive (mobile-first) con menu fullscreen en movil
- Header sticky con blur al hacer scroll
- SEO optimizado (meta tags, Open Graph, slugs amigables, sitemap)
- RSS Feed
- Cookie consent (GDPR)
- Lista de deseos (wishlist)
- Propiedades vistas recientemente (cookie-based)
- Carga dinamica de ciudades por estado (AJAX)

---

### Cuentas de Agentes/Propietarios

- Registro y login de agentes (con verificacion de email)
- Login social: Facebook, Google, GitHub, LinkedIn
- Panel privado del agente: publicar propiedades, ver transacciones
- Sistema de creditos para publicar propiedades
- Paquetes de publicacion (planes de suscripcion)
- Checkout con pasarela de pago integrada
- Facturas y historial de transacciones
- Configuracion de perfil, avatar y seguridad

---

### Sistema de Pagos

- Pasarelas integradas: Stripe, PayPal, Paystack, Razorpay, SSLCommerz
- Paquetes de creditos para agentes
- Cupones de descuento (crear, editar, aplicar)
- Facturas autogeneradas con template personalizable
- Historial de transacciones

---

### Resenas y Calificaciones

- Sistema de reviews por propiedad
- Calificacion con estrellas
- Moderacion desde el admin

---

### Clientes e Inversionistas

- Gestion de clientes (contactos del negocio)
- Gestion de inversionistas
- Vinculacion con leads y tableros

---

### Blog / CMS

- Crear y gestionar articulos del blog
- Categorias y tags
- Editor visual de contenido
- SEO por articulo
- Publicacion programada

---

### Administracion General

- Dashboard principal del admin con estadisticas
- Gestion de usuarios y roles
- Media manager (gestor de archivos e imagenes)
- Backup del sistema
- Audit log (registro de acciones)
- Google Analytics integrado
- Configuracion general del sitio (nombre, logo, SEO, moneda, idioma)
- CAPTCHA configurable (reCAPTCHA)

---

### Multi-Tenancy

- Un solo despliegue sirve multiples empresas (tenants)
- Cada tenant tiene su propia base de datos
- Dominio personalizado por tenant (subdominio o dominio propio)
- Gestion centralizada de tenants desde el admin central
- Base de datos central para datos compartidos (leads de WhatsApp, configuracion del bot)
- Aislamiento completo de datos entre tenants

---

## Funcionalidades Premium

*Las siguientes funcionalidades utilizan Inteligencia Artificial y/o integracion avanzada con WhatsApp Business API. Representan el diferenciador de valor del sistema.*

---

### [PREMIUM] Bot de WhatsApp con Inteligencia Artificial

Bot conversacional que atiende clientes automaticamente por WhatsApp, busca propiedades o vehiculos en la base de datos y responde en lenguaje natural.

**Tecnologia**: Anthropic Claude (Haiku 4.5 / Sonnet 5) via API directa (HTTP, sin SDK).

#### Capacidades del Bot

- **Atencion automatica 24/7**: responde consultas de clientes en cualquier horario
- **Busqueda inteligente de propiedades**: el cliente describe lo que busca en lenguaje natural ("busco casa de 3 habitaciones en Escazu por menos de $200k") y el bot busca en la BD
- **Busqueda inteligente de vehiculos**: el cliente pide por marca, modelo, ano, precio, transmision, combustible, color, condicion
- **Envio de fotos de productos**: el bot envia hasta 3 imagenes de las propiedades/vehiculos encontrados via WhatsApp
- **Conversaciones multi-turno**: el bot recuerda el contexto de la conversacion (ultimos 10 mensajes) y mantiene coherencia
- **Tool Use (Function Calling)**: Claude decide autonomamente que herramienta usar:
  - `search_properties` — buscar propiedades con filtros
  - `get_property_detail` — obtener detalle completo de una propiedad
  - `search_vehicles` — buscar vehiculos con filtros
  - `get_vehicle_detail` — obtener detalle de un vehiculo
- **Loop agentico**: hasta 3 iteraciones de busqueda por mensaje (busca → no encuentra → relaja filtros → busca de nuevo)
- **Formato WhatsApp nativo**: respuestas con *negrita*, emojis moderados, estructura legible
- **System prompt configurable**: personalizar la personalidad y reglas del bot desde la UI
- **Mensaje fallback**: si la IA falla, envia un mensaje predeterminado

#### Modos de operacion

- **Modo Inmobiliario (multi-tenant)**: el bot presenta las empresas disponibles y el cliente elige con cual consultar. Las busquedas se ejecutan en la BD del tenant seleccionado.
- **Modo Vehiculos (concesionario)**: busqueda de vehiculos via API REST externa. Un solo concesionario, sin seleccion de tenant.
- **Cambio de empresa**: el cliente puede escribir "cambiar" para elegir otra empresa inmobiliaria en cualquier momento.

#### Costo estimado

| Modelo | Costo por conversacion | 500 chats/mes |
|---|---|---|
| Claude Haiku 4.5 | ~$0.004 | ~$2/mes |
| Claude Sonnet 5 | ~$0.05 | ~$25/mes |

---

### [PREMIUM] Captura Automatica de Leads desde Meta

Captura leads de 4 canales de Meta automaticamente, sin intervencion manual:

- **Facebook Lead Ads**: formularios de leads en anuncios de Facebook
- **Instagram DMs**: mensajes directos de Instagram
- **Facebook Messenger**: conversaciones de Messenger
- **WhatsApp Business**: mensajes de WhatsApp

#### Arquitectura

- Webhook unico (`/webhook/meta`) recibe todos los eventos
- Validacion HMAC SHA-256 de cada payload (seguridad)
- Job en cola (queue) para procesamiento asincrono (3 reintentos)
- Log completo de webhooks con opcion de reintento manual
- Auto-importacion configurable (activar/desactivar)
- Asignacion automatica de leads al agente por defecto

#### Datos capturados

- Nombre del contacto
- Telefono
- Plataforma de origen (Facebook, Instagram, WhatsApp, Messenger)
- Fecha de primer contacto
- Lead ID de Meta
- Historial completo de conversacion (solo WhatsApp)

---

### [PREMIUM] Flujos de Conversacion (Bot Flow Builder)

Constructor visual de flujos de conversacion guiados para el bot de WhatsApp:

- **Flujos por tenant**: cada empresa puede tener su propio flujo
- **Opciones tipo menu**: el bot presenta opciones y el cliente elige
- **Pasos de recoleccion de datos**: preguntas secuenciales (texto libre, seleccion multiple, si/no)
- **Mensajes interactivos de WhatsApp**: botones (hasta 3) y listas (hasta 10 opciones) nativos
- **Saludo personalizable**: mensaje de bienvenida configurable por flujo
- **Trigger a IA**: un flujo puede recolectar datos iniciales y luego pasar el control a la IA con todo el contexto
- **Completado con notificacion**: al completar un flujo, se envia email y/o WhatsApp al agente responsable

#### Ejemplo de flujo

```
Bot: "Hola! Que te gustaria hacer?"
  1. Comprar propiedad
  2. Alquilar propiedad
  3. Vender mi propiedad

Cliente: "1"

Bot: "En que zona buscas?"
Cliente: "Escazu"

Bot: "Presupuesto?"
  - Menos de $100k
  - $100k - $200k
  - $200k - $500k
  - Mas de $500k

→ Completa el flujo → Notifica al agente → IA toma el control con el contexto
```

---

### [PREMIUM] Dashboard de Conversaciones del Bot

Panel de metricas y actividad del bot por empresa (tenant):

- **Total de mensajes** recibidos
- **Contactos unicos** atendidos
- **Flujos completados**
- **Leads generados** via WhatsApp
- **Selector de tenant** para ver metricas por empresa
- **Mensajes por dia** (grafico temporal)
- **Contactos mas activos**

---

### [PREMIUM] Notificaciones Inteligentes a Agentes

Cuando el bot completa un flujo de conversacion o captura un lead:

- **Email automatico al agente**: usando SMTP configurado por tenant (no el SMTP global del sistema)
- **WhatsApp al agente**: notificacion via template de WhatsApp o mensaje libre
- **Datos del lead incluidos**: nombre, telefono, respuestas del flujo, canal de origen
- **Configuracion por tenant**: cada empresa configura sus destinatarios y credenciales de correo

---

### [PREMIUM] Features por Tenant (Sistema de Plan)

Control granular de funcionalidades premium por empresa:

| Feature | Descripcion |
|---|---|
| `photos_enabled` | Bot envia fotos de propiedades por WhatsApp |
| `interactive_buttons_enabled` | Botones y listas nativos de WhatsApp |
| `dashboard_enabled` | Acceso al dashboard de metricas del bot |
| `pdf_documents_enabled` | Generacion de documentos PDF |

- **Fecha de expiracion del plan**: si el plan expira, todas las features se desactivan automaticamente
- **Activacion/desactivacion individual** desde el admin central

---

### [PREMIUM] Integracion con Google Calendar

- Conexion OAuth con Google Calendar
- Sincronizacion bidireccional de eventos
- Los eventos de Google Calendar aparecen en el calendario del CRM
- Crear eventos en Google Calendar desde el CRM

---

### [PREMIUM] Configuracion de IA desde la UI

Todo se configura sin tocar codigo:

- **API Key de Anthropic**: se guarda cifrado en la BD
- **Seleccion de modelo**: Haiku 4.5 (rapido y economico) o Sonnet 5 (mayor calidad)
- **System prompt personalizado**: modificar la personalidad del bot desde un textarea
- **Proveedor de IA dual**: soporte para Claude (Anthropic) y GPT (OpenAI)
- **Maximo de resultados** por respuesta (1-10)
- **Mensaje fallback** cuando la IA no esta disponible
- **Test de conexion** (verificar que la API key funciona)

---

## Stack Tecnologico

| Componente | Tecnologia |
|---|---|
| Backend | Laravel (PHP) |
| Frontend admin | Blade + jQuery + Material Icons |
| Frontend publico | Tema Flex Home (Bootstrap + jQuery) |
| Base de datos | MySQL (multi-tenant) |
| Mapas | Google Maps API |
| Pagos | Stripe, PayPal, Paystack, Razorpay, SSLCommerz |
| IA | Anthropic Claude API (HTTP directo) |
| WhatsApp | WhatsApp Cloud API (Graph API v25.0) |
| Meta Leads | Facebook Graph API (Webhooks) |
| Calendario | Google Calendar API (OAuth 2.0) |
| Login social | Facebook, Google, GitHub, LinkedIn |
| Media | RvMedia (gestor integrado) |
| Colas | Laravel Queue (database/Redis) |
| Cache | Laravel Cache |

---

## Arquitectura Multi-Tenant

```
                    ┌──────────────────────┐
                    │   Base de datos       │
                    │   CENTRAL (mysql)     │
                    │                       │
                    │  - tenants            │
                    │  - settings           │
                    │  - whatsapp_convos    │
                    │  - webhook_logs       │
                    │  - bot_flows          │
                    │  - tenant_features    │
                    │  - mail_configs       │
                    └─────────┬────────────┘
                              │
               ┌──────────────┼──────────────┐
               │              │              │
               ▼              ▼              ▼
    ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
    │  BD Tenant 1 │ │  BD Tenant 2 │ │  BD Tenant N │
    │              │ │              │ │              │
    │ - properties │ │ - properties │ │ - properties │
    │ - categories │ │ - categories │ │ - categories │
    │ - crm_leads  │ │ - crm_leads  │ │ - crm_leads  │
    │ - accounts   │ │ - accounts   │ │ - accounts   │
    │ - reviews    │ │ - reviews    │ │ - reviews    │
    │ - blog       │ │ - blog       │ │ - blog       │
    └──────────────┘ └──────────────┘ └──────────────┘

    tenant1.safeworsolutions.com
    tenant2.safeworsolutions.com
    tenantN.safeworsolutions.com
```

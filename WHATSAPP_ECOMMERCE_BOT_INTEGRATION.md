# Integración de Bot de WhatsApp con IA para E-commerce (Moda)

## Resumen

Integrar un bot de WhatsApp potenciado por IA (Anthropic Claude) en el E-commerce de moda. El bot actuará como una vendedora real del negocio: responderá consultas sobre productos, enviará imágenes según lo que el cliente pida (talla, color, estilo), identificará productos a partir de fotos que envíe el cliente, y mantendrá conversaciones naturales indistinguibles de una persona humana. Se activará automáticamente si el chat lleva 30 minutos sin respuesta humana.

---

## 1. Arquitectura de referencia (Proyecto Inmobiliaria)

El proyecto CRM Inmobiliaria ya tiene un bot de WhatsApp funcionando con Claude. Esta es la arquitectura probada que se debe replicar y adaptar para el E-commerce.

### 1.1 Flujo completo actual (ya funcionando)

```
WhatsApp Cloud API (Meta)
       │
       │  POST /webhook/meta
       ▼
┌─────────────────────────┐
│  MetaWebhookController  │ ── Valida firma HMAC (X-Hub-Signature-256)
│  (handle)               │ ── Determina tipo: 'whatsapp'
│                         │ ── Crea MetaWebhookLog
│                         │ ── Despacha Job a cola
└──────────┬──────────────┘
           │ dispatch()
           ▼
┌─────────────────────────┐
│  ProcessMetaWebhook     │ ── Job con cola (ShouldQueue, 3 reintentos)
│  (Queue Job)            │ ── Llama a MetaLeadService
└──────────┬──────────────┘
           │
           ▼
┌─────────────────────────┐
│  MetaLeadService        │ ── Extrae: teléfono, nombre, texto del mensaje
│  (processWhatsAppMsg)   │ ── Soporta tipos: text, interactive, image, audio, etc.
│                         │ ── Si bot habilitado → WhatsAppBotService
└──────────┬──────────────┘
           │
           ▼
┌──────────────────────────────────────────────────────────┐
│  WhatsAppBotService  (1611 líneas)                       │
│                                                          │
│  1. Resuelve tenant activo (multi-tenant)                │
│  2. Carga historial de conversación (últimos 10 msgs)    │
│  3. Construye system prompt + tool definitions           │
│  4. Llama a Claude API (HTTP directo, sin SDK)           │
│  5. Loop de tool_use (máx 3 iteraciones):                │
│     ├── search_properties → PropertySearchService        │
│     └── get_property_detail → PropertySearchService      │
│  6. Envía respuesta texto vía WhatsApp Cloud API         │
│  7. Envía fotos de propiedades (hasta 3 imágenes)        │
│  8. Guarda inbound + outbound en re_whatsapp_conversations│
└──────────────────────────────────────────────────────────┘
           │
           ▼
┌─────────────────────────┐
│  WhatsApp Cloud API     │ ── graph.facebook.com/v25.0/{phoneId}/messages
│  (Respuesta al cliente) │ ── Tipos: text, image, interactive, document
└─────────────────────────┘
```

### 1.2 Cómo se conecta con la API de Claude (código real)

```php
// Archivo: WhatsAppBotService.php → callClaude()
// HTTP directo — sin SDK, solo Http::post()

$response = Http::timeout(30)
    ->withHeaders([
        'x-api-key'         => $apiKey,           // setting('crm_whatsapp_bot_llm_api_key')
        'anthropic-version' => '2023-06-01',
        'content-type'      => 'application/json',
    ])
    ->post('https://api.anthropic.com/v1/messages', [
        'model'      => $model,        // 'claude-haiku-4-5-20251001'
        'max_tokens' => 1024,
        'system'     => $systemPrompt, // Instrucciones del bot
        'tools'      => $tools,        // Herramientas (search, detail, etc.)
        'messages'   => $messages,     // Historial de conversación
    ]);
```

### 1.3 Cómo funciona el Tool Use (loop agentico)

Claude no busca en la base de datos directamente. Se le definen "herramientas" que puede decidir usar según la conversación:

```
Cliente: "Tienen casas en Escazú por menos de $200k?"
       │
       ▼
Claude decide usar: search_properties({ keyword: "Escazú", max_price: 200000, type: "sale" })
       │
       ▼
El código PHP ejecuta la búsqueda real en la BD
       │
       ▼
Devuelve resultados JSON a Claude
       │
       ▼
Claude formatea la respuesta en lenguaje natural para WhatsApp
       │
       ▼
"¡Hola! 🏡 Encontré 3 opciones en Escazú:
 1. *Casa Moderna Escazú* — $185,000 — 3 hab, 2 baños
 2. *Residencia Trejos* — $192,000 — 4 hab, 3 baños
 ..."
```

El loop puede ejecutarse hasta 3 veces por mensaje (ej: busca → no encuentra → relaja filtros → busca de nuevo → detalle de una propiedad).

### 1.4 Envío de mensajes/imágenes por WhatsApp

Todos los envíos usan la **WhatsApp Cloud API** (Graph API v25.0):

```php
// Enviar texto
Http::withHeaders(['Authorization' => "Bearer {$token}"])
    ->post("https://graph.facebook.com/v25.0/{$phoneNumberId}/messages", [
        'messaging_product' => 'whatsapp',
        'to'   => $to,
        'type' => 'text',
        'text' => ['body' => mb_substr($message, 0, 4096)],
    ]);

// Enviar imagen con caption
Http::withHeaders(['Authorization' => "Bearer {$token}"])
    ->post("https://graph.facebook.com/v25.0/{$phoneNumberId}/messages", [
        'messaging_product' => 'whatsapp',
        'to'   => $to,
        'type' => 'image',
        'image' => [
            'link'    => $imageUrl,     // URL pública de la imagen
            'caption' => $caption,      // Máx 1024 chars
        ],
    ]);

// Enviar mensaje interactivo (botones o listas)
Http::withHeaders(['Authorization' => "Bearer {$token}"])
    ->post("https://graph.facebook.com/v25.0/{$phoneNumberId}/messages", [
        'messaging_product' => 'whatsapp',
        'to'   => $to,
        'type' => 'interactive',
        'interactive' => $interactive,  // Lista o botones
    ]);
```

### 1.5 Gestión de conversaciones (multi-turn)

Cada mensaje (entrante y saliente) se guarda en `re_whatsapp_conversations`:

```
| phone       | tenant_id | direction | message                        | metadata |
|-------------|-----------|-----------|--------------------------------|----------|
| 50688881234 | tienda1   | inbound   | Hola, tienen vestidos rojos?   | {}       |
| 50688881234 | tienda1   | outbound  | ¡Hola! Sí, tenemos 3 opciones… | {}       |
| 50688881234 | tienda1   | inbound   | El segundo, en talla M?        | {}       |
| 50688881234 | tienda1   | outbound  | ¡Sí! El vestido tiene talla M… | {}       |
```

Antes de cada llamada a Claude, se cargan los **últimos 10 mensajes** como contexto:

```php
// getConversationContext()
WhatsAppConversation::query()
    ->where('phone', $phone)
    ->where('tenant_id', $tenantId)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get()
    ->reverse()  // Orden cronológico
    ->map(fn ($c) => [
        'direction' => $c->direction,  // inbound = 'user', outbound = 'assistant'
        'message'   => $c->message,
    ]);
```

---

## 2. Adaptación para E-commerce de Moda

### 2.1 Lo que cambia respecto al proyecto Inmobiliaria

| Aspecto | Inmobiliaria (actual) | E-commerce (nuevo) |
|---|---|---|
| **Productos** | Propiedades inmobiliarias | Ropa, accesorios, zapatos |
| **Filtros de búsqueda** | Ubicación, precio, habitaciones | Talla, color, categoría, precio, disponibilidad |
| **Imágenes recibidas** | No se procesan con IA | Claude Vision analiza la foto y busca producto similar |
| **Personalidad del bot** | Asesor inmobiliario profesional | Vendedora real del negocio (tono, estilo, emojis) |
| **Activación** | Siempre activo | Solo si no hay respuesta humana en 30 min |
| **Entrenamiento** | System prompt genérico | Screenshots de conversaciones reales como ejemplo |
| **Multi-tenant** | Sí (varias inmobiliarias) | No necesario (una sola tienda) |
| **Envío de fotos** | Hasta 3 por búsqueda | Hasta 5 con info de precio y talla |

### 2.2 Diagrama del flujo E-commerce

```
                    Cliente envía mensaje por WhatsApp
                                    │
                                    ▼
                    ┌───────────────────────────────┐
                    │   ¿Hay respuesta humana en    │
                    │    los últimos 30 minutos?     │
                    └───────────┬───────────────────┘
                           ┌────┴────┐
                          Sí         No
                           │          │
                           ▼          ▼
                    No hacer nada   ┌──────────────────────────┐
                    (humano atiende)│  WhatsAppBotService      │
                                   │  (IA se activa)           │
                                   └──────────┬───────────────┘
                                              │
                              ┌───────────────┼───────────────┐
                              │               │               │
                              ▼               ▼               ▼
                    ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐
                    │ Msg de texto │ │ Msg con foto │ │ Interactivo      │
                    │              │ │              │ │ (botón/lista)    │
                    └──────┬───────┘ └──────┬───────┘ └───────┬──────────┘
                           │               │                  │
                           ▼               ▼                  ▼
                    Texto directo   Descargar imagen    Extraer selección
                    al LLM          vía Media API       como texto
                                   + enviar como
                                   bloque image a
                                   Claude Vision
                                          │
                           ┌──────────────┘
                           ▼
                ┌─────────────────────────────┐
                │  Claude API (Messages)       │
                │                              │
                │  System prompt:              │
                │  - Personalidad entrenada    │
                │  - Ejemplos de conversación  │
                │  - Reglas del negocio        │
                │                              │
                │  Tools disponibles:          │
                │  - search_products           │
                │  - get_product_detail        │
                │  - search_by_image           │
                │  - check_availability        │
                └──────────┬──────────────────┘
                           │
                           ▼
                ┌─────────────────────────────┐
                │  Tool Use Loop (máx 3)       │
                │                              │
                │  Claude decide qué buscar:   │
                │  "Vestidos rojos talla M"    │
                │        │                     │
                │        ▼                     │
                │  ProductSearchService        │
                │  ejecuta query en BD         │
                │        │                     │
                │        ▼                     │
                │  Devuelve JSON con productos │
                │  + URLs de imágenes          │
                │        │                     │
                │        ▼                     │
                │  Claude formatea respuesta   │
                │  natural para WhatsApp       │
                └──────────┬──────────────────┘
                           │
                           ▼
                ┌─────────────────────────────┐
                │  Respuesta al cliente        │
                │                              │
                │  1. Texto con info productos │
                │  2. Hasta 5 fotos con precio │
                │     y tallas disponibles     │
                └─────────────────────────────┘
```

---

## 3. Requerimientos funcionales

### 3.1 Entrenamiento del bot con conversaciones reales

**Objetivo**: Que el bot responda exactamente como lo hacen las dueñas del negocio — mismo tono, mismas expresiones, mismos emojis, misma calidez.

**Implementación**: Subir screenshots de conversaciones reales de WhatsApp desde la UI del admin. Claude Vision los analiza y extrae el estilo de comunicación.

#### Flujo de entrenamiento:

```
┌─ Configuración del Bot ─────────────────────────────────────┐
│                                                              │
│  📚 Entrenamiento con conversaciones reales                 │
│                                                              │
│  Subí capturas de pantalla de chats reales del negocio.     │
│  La IA analizará el estilo y lo imitará.                    │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  [📸 Subir screenshots]                              │   │
│  │                                                      │   │
│  │  Screenshots subidos: 12                             │   │
│  │  ┌────────────────────────────────────────────────┐  │   │
│  │  │ chat-ejemplo-1.jpg  ✅ Analizado               │  │   │
│  │  │ chat-ejemplo-2.jpg  ✅ Analizado               │  │   │
│  │  │ chat-ejemplo-3.jpg  ⏳ Pendiente               │  │   │
│  │  └────────────────────────────────────────────────┘  │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
│  [🤖 Generar perfil de comunicación]                        │
│                                                              │
│  ═══════════════════════════════════════════                 │
│  Perfil generado:                                           │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ "La comunicación es cálida y cercana. Usa 'amor',    │   │
│  │  'mi vida', 'linda'. Emojis frecuentes: 💕🌸✨🛍️.   │   │
│  │  Responde con entusiasmo sobre los productos.        │   │
│  │  Ofrece opciones sin presionar. Usa 'te queda        │   │
│  │  divino', 'super lindo'. Pregunta talla antes de     │   │
│  │  recomendar. Cierra con '¿te lo aparto?' o           │   │
│  │  '¿te lo separo?'..."                                │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
│  [✏️ Editar perfil]  [💾 Guardar y aplicar]                 │
└──────────────────────────────────────────────────────────────┘
```

#### Implementación técnica del entrenamiento:

```php
class ConversationTrainingService
{
    /**
     * Analizar screenshots de conversaciones con Claude Vision
     * y generar un perfil de comunicación.
     */
    public function analyzeScreenshots(array $imagePaths): string
    {
        $apiKey = setting('wa_bot_api_key');
        $content = [];

        foreach ($imagePaths as $path) {
            $imageData = base64_encode(file_get_contents($path));
            $mimeType = mime_content_type($path);

            $content[] = [
                'type' => 'image',
                'source' => [
                    'type'       => 'base64',
                    'media_type' => $mimeType,
                    'data'       => $imageData,
                ],
            ];
        }

        $content[] = [
            'type' => 'text',
            'text' => <<<PROMPT
Analizá estas capturas de conversaciones reales de WhatsApp de una tienda de ropa/moda.

Extraé un perfil de comunicación detallado que incluya:

1. **Tono general**: ¿Formal, informal, cálido, juvenil, profesional?
2. **Expresiones frecuentes**: Palabras o frases que se repiten (ej: "amor", "mi vida", "te queda divino")
3. **Emojis**: ¿Cuáles usa? ¿Con qué frecuencia?
4. **Estructura de respuesta**: ¿Cómo abre el mensaje? ¿Cómo cierra? ¿Ofrece opciones?
5. **Estrategia de venta**: ¿Pregunta talla primero? ¿Ofrece alternativas? ¿Usa urgencia? ¿Menciona precios proactivamente?
6. **Manejo de objeciones**: ¿Cómo responde a "es muy caro", "no hay mi talla", "lo pienso"?
7. **Despedida/cierre**: ¿Cómo intenta cerrar la venta? (ej: "¿te lo aparto?", "¿hacemos el pedido?")
8. **Errores a evitar**: Cosas que la vendedora NUNCA haría (ser fría, usar lenguaje técnico, etc.)

Devolvé el perfil como un texto que pueda usarse directamente como parte del system prompt de un LLM.
PROMPT,
        ];

        $response = Http::timeout(60)
            ->withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-sonnet-5',  // Sonnet para análisis detallado
                'max_tokens' => 4096,
                'messages'   => [['role' => 'user', 'content' => $content]],
            ]);

        return $response->json('content.0.text', '');
    }
}
```

#### Cómo se usa el perfil en el system prompt:

```php
protected function buildSystemPrompt(): string
{
    $storeName = setting('wa_bot_store_name') ?: 'la tienda';
    $trainingProfile = setting('wa_bot_training_profile');  // Generado del análisis
    $customRules = setting('wa_bot_custom_rules');

    $prompt = <<<PROMPT
Sos una vendedora real de {$storeName} atendiendo por WhatsApp. NO sos un bot — sos una persona real del equipo de ventas.

PERSONALIDAD Y ESTILO DE COMUNICACIÓN:
{$trainingProfile}

REGLAS ABSOLUTAS:
- NUNCA digas que sos un bot, IA, o asistente virtual. Sos parte del equipo de {$storeName}.
- NUNCA inventés productos. Solo mostrá lo que devuelve la herramienta de búsqueda.
- Respondé en español, con el estilo exacto del perfil de comunicación de arriba.
- Formateá para WhatsApp: *negrita* para nombres y precios, emojis según el perfil.
- Máximo 5 productos por mensaje.
- Si el cliente envía una imagen, analizala y buscá productos similares.
- Si preguntan por disponibilidad de talla, verificá con la herramienta antes de responder.
- Si no hay stock en la talla pedida, ofrecé tallas alternativas o productos similares.
- Si el cliente quiere comprar, indicá el proceso de pedido (link, transferencia, etc.).

ESTRATEGIA DE BÚSQUEDA:
- Empezá con pocos filtros. Usá "keyword" para texto libre.
- NUNCA repitas una búsqueda exacta que ya devolvió 0 resultados.
- Si no hay match, quitá filtros y ampliá la búsqueda.
- Siempre verificá talla/stock con check_availability antes de confirmar.
PROMPT;

    if ($customRules) {
        $prompt .= "\n\nREGLAS ADICIONALES DEL NEGOCIO:\n{$customRules}";
    }

    return $prompt;
}
```

### 3.2 Búsqueda y envío de productos

#### Tools (herramientas) para Claude — E-commerce:

```php
protected function getToolDefinitions(): array
{
    return [
        // 1. BUSCAR PRODUCTOS
        [
            'name' => 'search_products',
            'description' => 'Buscar productos de la tienda según lo que pida el cliente.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'keyword'   => [
                        'type' => 'string',
                        'description' => 'Texto libre. Busca en nombre, descripción, categoría, tags.',
                    ],
                    'category'  => [
                        'type' => 'string',
                        'description' => 'Categoría (ej: vestidos, blusas, pantalones, zapatos, accesorios)',
                    ],
                    'size'      => [
                        'type' => 'string',
                        'description' => 'Talla (ej: XS, S, M, L, XL, XXL, o numérica: 6, 8, 10, 36, 38)',
                    ],
                    'color'     => [
                        'type' => 'string',
                        'description' => 'Color del producto',
                    ],
                    'min_price' => [
                        'type' => 'number',
                        'description' => 'Precio mínimo',
                    ],
                    'max_price' => [
                        'type' => 'number',
                        'description' => 'Precio máximo',
                    ],
                    'in_stock'  => [
                        'type' => 'boolean',
                        'description' => 'true para mostrar solo productos con stock disponible',
                    ],
                    'new_arrivals' => [
                        'type' => 'boolean',
                        'description' => 'true para mostrar solo los productos más recientes',
                    ],
                ],
                'required' => [],
            ],
        ],

        // 2. DETALLE DE PRODUCTO
        [
            'name' => 'get_product_detail',
            'description' => 'Obtener info completa de un producto: todas las tallas disponibles, colores, fotos, descripción.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'product_id' => [
                        'type' => 'integer',
                        'description' => 'ID del producto',
                    ],
                ],
                'required' => ['product_id'],
            ],
        ],

        // 3. VERIFICAR DISPONIBILIDAD
        [
            'name' => 'check_availability',
            'description' => 'Verificar si un producto específico tiene stock en una talla y color determinados.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'product_id' => [
                        'type' => 'integer',
                        'description' => 'ID del producto',
                    ],
                    'size' => [
                        'type' => 'string',
                        'description' => 'Talla a verificar',
                    ],
                    'color' => [
                        'type' => 'string',
                        'description' => 'Color a verificar (opcional)',
                    ],
                ],
                'required' => ['product_id'],
            ],
        ],

        // 4. BUSCAR POR IMAGEN (nuevo — no existe en Inmobiliaria)
        [
            'name' => 'search_by_image',
            'description' => 'Buscar productos similares a partir de la descripción visual de una imagen que envió el cliente. Usá esta herramienta cuando el cliente mande una foto de referencia.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'visual_description' => [
                        'type' => 'string',
                        'description' => 'Descripción detallada de la prenda en la imagen: tipo de prenda, color, material, estilo, corte, largo, estampado, etc.',
                    ],
                ],
                'required' => ['visual_description'],
            ],
        ],
    ];
}
```

#### Ejemplo de conversación — Búsqueda por talla:

```
Cliente: "Hola! Qué tienen en talla M?"

Claude analiza → usa tool: search_products({ size: "M", in_stock: true })

PHP ejecuta la query → devuelve 5 productos con stock en M

Claude responde (imitando a la dueña):
"¡Hola amor! 💕 Mirá lo que tenemos en talla M:

1. *Vestido floral Garden* — ₡15,900
2. *Blusa off-shoulder Rosa* — ₡9,500
3. *Conjunto crop + falda Luna* — ₡18,200
4. *Jeans skinny Azul* — ₡12,800
5. *Top tejido Beige* — ₡7,900

¿Alguno te llama la atención? Te mando fotitos 📸✨"

→ Envía 5 imágenes con caption (precio + nombre)
```

#### Ejemplo de conversación — Búsqueda por imagen:

```
Cliente: [Envía foto de un vestido rojo que vio en Instagram]
         "Tienen algo parecido a esto?"

                    │
                    ▼
┌─────────────────────────────────────────────────────────┐
│  1. WhatsApp Cloud API notifica imagen recibida         │
│  2. Bot descarga la imagen vía Media API de WhatsApp    │
│     GET graph.facebook.com/v25.0/{media_id}             │
│     → obtiene URL temporal                              │
│     GET {url} con Bearer token → descarga binario       │
│  3. Envía imagen a Claude como bloque 'image'           │
│     junto con el historial de conversación               │
│  4. Claude analiza la imagen y describe la prenda:      │
│     "Vestido rojo, largo midi, corte A, escote V,       │
│      tela fluida, sin mangas"                           │
│  5. Claude usa tool: search_by_image({                  │
│       visual_description: "vestido rojo midi corte A    │
│       escote V tela fluida sin mangas"                  │
│     })                                                  │
│  6. ProductSearchService busca por keywords derivados   │
│  7. Claude responde con los matches encontrados         │
└─────────────────────────────────────────────────────────┘

Claude responde:
"¡Ay qué lindo! 😍 Tengo algo super parecido:

*Vestido Scarlet* — ₡17,500
Rojo, largo midi, corte en A. ¡Te queda divino! 

Tallas disponibles: S, M, L
¿En qué talla te lo separo amor? 💕"

→ Envía la foto del producto
```

### 3.3 Activación del bot después de 30 minutos sin respuesta humana

**Concepto**: El bot NO responde de inmediato. Le da prioridad al humano. Si nadie del equipo contesta en 30 minutos, el bot toma el control.

#### Implementación:

```php
// En MetaLeadService::processWhatsAppMessage() — modificar el flujo

public function processWhatsAppMessage(array $entry): void
{
    // ... extraer mensaje ...

    // Guardar mensaje entrante inmediatamente
    WhatsAppConversation::create([
        'phone'     => $from,
        'direction' => 'inbound',
        'message'   => $messageText,
        'metadata'  => ['requires_response' => true, 'received_at' => now()],
    ]);

    // NO llamar al bot inmediatamente
    // En su lugar, despachar un Job con delay de 30 minutos
    ProcessBotResponse::dispatch($from, $contactName, $messageText, $metadata)
        ->delay(now()->addMinutes(30));
}
```

```php
// Job: ProcessBotResponse — se ejecuta 30 min después

class ProcessBotResponse implements ShouldQueue
{
    public function handle(): void
    {
        // Verificar si hubo respuesta humana en los últimos 30 minutos
        $humanResponse = WhatsAppConversation::query()
            ->where('phone', $this->phone)
            ->where('direction', 'outbound')
            ->where('is_human', true)       // Flag para distinguir humano vs bot
            ->where('created_at', '>=', $this->receivedAt)
            ->exists();

        if ($humanResponse) {
            // Un humano ya respondió — no hacer nada
            return;
        }

        // Verificar si el bot ya respondió este mensaje
        $botResponse = WhatsAppConversation::query()
            ->where('phone', $this->phone)
            ->where('direction', 'outbound')
            ->where('is_human', false)
            ->where('created_at', '>=', $this->receivedAt)
            ->exists();

        if ($botResponse) {
            return; // Bot ya respondió — evitar duplicados
        }

        // Ningún humano respondió → activar bot
        $botService = app(WhatsAppBotService::class);
        $botService->processIncomingMessage(
            $this->phone,
            $this->contactName,
            $this->messageText,
            $this->metadata
        );
    }
}
```

#### Flujo visual de la regla de 30 minutos:

```
Minuto 0:  Cliente envía mensaje
           → Se guarda en BD
           → Se agenda Job para minuto 30
           → Notificación al equipo humano (push/sonido en admin)

Minuto 5:  (Escenario A) Humano responde desde el admin
           → Se guarda con is_human = true
           → Cuando el Job se ejecute en minuto 30, detecta
             la respuesta humana y se cancela silenciosamente

Minuto 30: (Escenario B) Nadie respondió
           → Job verifica: ¿hay outbound humano después del inbound?
           → No hay → Bot se activa y responde
           → Se guarda con is_human = false

Minuto 35: (Escenario C) Cliente envía otro mensaje MIENTRAS espera
           → Se agenda OTRO Job para minuto 65
           → Si el bot ya respondió el primer mensaje, se acumula
             en el historial y el bot puede continuar la conversación
```

#### Configuración desde la UI:

```
┌─ Activación del Bot ────────────────────────────────────┐
│                                                          │
│  Modo de activación:                                     │
│  ○ Siempre activo (responde de inmediato)               │
│  ● Activar después de inactividad humana                │
│  ○ Solo manual (el admin activa/desactiva por chat)     │
│                                                          │
│  Tiempo de espera: [30] minutos                         │
│  (Tiempo que se espera por respuesta humana              │
│   antes de activar el bot)                               │
│                                                          │
│  Horario del bot:                                        │
│  ☑ Activar fuera de horario laboral sin espera           │
│    Horario laboral: [08:00] a [18:00]                   │
│    (Fuera de horario, el bot responde de inmediato)      │
│                                                          │
│  ☑ Notificar al equipo cuando el bot se activa          │
└──────────────────────────────────────────────────────────┘
```

### 3.4 Toda respuesta vía modelo IA de Anthropic

**WhatsApp Cloud API es solo el conductor.** No tiene lógica de negocio, no decide qué responder, no tiene flujos hardcodeados. Es un tubo que:
1. Recibe mensajes del cliente → los pasa a Claude
2. Recibe respuesta de Claude → la envía al cliente

```
┌──────────────┐     ┌──────────────────┐     ┌──────────────┐
│   Cliente    │◄───►│ WhatsApp Cloud   │◄───►│  Tu Servidor │
│  (WhatsApp)  │     │ API (Meta)       │     │  (Laravel)   │
└──────────────┘     │                  │     │      │       │
                     │ Solo transporta  │     │      ▼       │
                     │ mensajes.        │     │ ┌──────────┐ │
                     │ No procesa nada. │     │ │ Claude   │ │
                     └──────────────────┘     │ │ API      │ │
                                              │ │ (Cerebro)│ │
                                              │ └──────────┘ │
                                              │      │       │
                                              │      ▼       │
                                              │ ┌──────────┐ │
                                              │ │ BD E-com │ │
                                              │ │(productos│ │
                                              │ │ stock)   │ │
                                              │ └──────────┘ │
                                              └──────────────┘
```

**Claude toma TODAS las decisiones**:
- Qué herramienta usar (buscar, detallar, verificar stock)
- Qué filtros aplicar (talla, color, precio)
- Cómo formular la respuesta (tono, emojis, estructura)
- Cuándo ofrecer alternativas
- Cuándo sugerir el cierre de venta
- Cómo manejar objeciones

**El código PHP solo**:
- Recibe/envía mensajes por WhatsApp API
- Ejecuta las búsquedas que Claude pide (tool_use)
- Guarda historial de conversaciones
- Controla la regla de 30 minutos

---

## 4. Procesamiento de imágenes del cliente

### 4.1 Recibir y descargar la imagen

Cuando un cliente envía una imagen por WhatsApp, Meta envía un `media_id` en el webhook. Hay que descargar la imagen antes de enviarla a Claude.

```php
// En MetaLeadService — expandir extractWhatsAppMessageText()

protected function extractWhatsAppMessageText(array $message): array
{
    $type = $message['type'] ?? 'text';

    if ($type === 'image') {
        $mediaId = $message['image']['id'] ?? null;
        $caption = $message['image']['caption'] ?? '';

        if ($mediaId) {
            $imageBase64 = $this->downloadWhatsAppMedia($mediaId);

            return [
                'text'    => $caption ?: '[El cliente envió una imagen]',
                'type'    => 'image',
                'image'   => $imageBase64,   // base64 encoded
                'caption' => $caption,
            ];
        }
    }

    // ... resto de tipos ...
}

/**
 * Descargar media de WhatsApp Cloud API.
 * Paso 1: GET media_id → obtener URL temporal
 * Paso 2: GET URL → descargar binario
 */
protected function downloadWhatsAppMedia(string $mediaId): ?string
{
    $token = setting('crm_meta_page_access_token');

    // Paso 1: Obtener URL del media
    $mediaInfo = Http::withHeaders([
        'Authorization' => "Bearer {$token}",
    ])->get("https://graph.facebook.com/v25.0/{$mediaId}");

    if (!$mediaInfo->successful()) {
        return null;
    }

    $mediaUrl = $mediaInfo->json('url');

    // Paso 2: Descargar el binario
    $imageResponse = Http::withHeaders([
        'Authorization' => "Bearer {$token}",
    ])->get($mediaUrl);

    if (!$imageResponse->successful()) {
        return null;
    }

    return base64_encode($imageResponse->body());
}
```

### 4.2 Enviar imagen a Claude Vision para análisis

```php
// En WhatsAppBotService — modificar callClaude() para soportar imágenes

protected function callClaude(string $message, array $history, string $apiKey, ?array $imageData = null): string
{
    $model = setting('wa_bot_llm_model') ?: 'claude-haiku-4-5-20251001';
    $systemPrompt = $this->buildSystemPrompt();

    $messages = [];
    foreach ($history as $msg) {
        $messages[] = [
            'role'    => $msg['direction'] === 'inbound' ? 'user' : 'assistant',
            'content' => $msg['message'],
        ];
    }

    // Si hay imagen, construir mensaje multimodal
    if ($imageData) {
        $userContent = [
            [
                'type'   => 'image',
                'source' => [
                    'type'       => 'base64',
                    'media_type' => $imageData['mime_type'] ?? 'image/jpeg',
                    'data'       => $imageData['base64'],
                ],
            ],
            [
                'type' => 'text',
                'text' => $message ?: 'El cliente envió esta imagen. Analizala y buscá productos similares en la tienda.',
            ],
        ];
        $messages[] = ['role' => 'user', 'content' => $userContent];
    } else {
        $messages[] = ['role' => 'user', 'content' => $message];
    }

    $tools = $this->getToolDefinitions();

    $response = Http::timeout(30)
        ->withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])
        ->post('https://api.anthropic.com/v1/messages', [
            'model'      => $model,
            'max_tokens' => 1024,
            'system'     => $systemPrompt,
            'tools'      => $tools,
            'messages'   => $messages,
        ]);

    // ... procesamiento de respuesta igual que antes ...
}
```

### 4.3 Flujo completo: imagen → análisis → búsqueda → respuesta

```
1. Cliente envía foto de un vestido negro por WhatsApp
       │
       ▼
2. Webhook recibe: type: "image", media_id: "abc123"
       │
       ▼
3. downloadWhatsAppMedia("abc123")
   → GET graph.facebook.com/v25.0/abc123 → URL temporal
   → GET URL → binario → base64
       │
       ▼
4. callClaude() con bloque image + texto:
   "El cliente envió esta imagen buscando algo parecido"
       │
       ▼
5. Claude VE la imagen y describe internamente:
   "Vestido negro, largo corto, corte ajustado,
    tirantes finos, tela satinada, escote recto"
       │
       ▼
6. Claude usa tool: search_by_image({
     visual_description: "vestido negro corto ajustado
     tirantes finos tela satinada escote recto"
   })
       │
       ▼
7. ProductSearchService busca con esos keywords
   combinados: "vestido negro corto satinado"
       │
       ▼
8. Devuelve 3 matches a Claude
       │
       ▼
9. Claude responde en el estilo entrenado:
   "¡Ay sí, qué lindo ese estilo! 😍 
    Mirá lo que tengo parecido:
    
    *Vestido Midnight* — ₡14,900
    Negro, tirantes, largo corto ✨
    Tallas: S, M, L
    
    *Mini vestido Elegance* — ₡16,500
    Negro satinado, super sexy 💃
    Tallas: XS, S, M
    
    ¿Cuál te gusta más amor? 💕"
       │
       ▼
10. Envía 2 fotos de los productos
```

---

## 5. Servicio de búsqueda de productos (`ProductSearchService`)

```php
class ProductSearchService
{
    /**
     * Buscar productos según los filtros que Claude decida aplicar.
     */
    public function searchProducts(array $params): Collection
    {
        $query = Product::query()
            ->where('status', 'active')
            ->with(['category', 'images', 'variants']);

        // Keyword: busca en nombre, descripción, tags, categoría
        if (!empty($params['keyword'])) {
            $kw = $params['keyword'];
            $query->where(function ($q) use ($kw) {
                $q->where('name', 'LIKE', "%{$kw}%")
                  ->orWhere('description', 'LIKE', "%{$kw}%")
                  ->orWhere('tags', 'LIKE', "%{$kw}%")
                  ->orWhereHas('category', fn($q2) => $q2->where('name', 'LIKE', "%{$kw}%"));
            });
        }

        // Categoría
        if (!empty($params['category'])) {
            $query->whereHas('category', fn($q) =>
                $q->where('name', 'LIKE', "%{$params['category']}%")
            );
        }

        // Talla: filtrar productos que tengan stock en esa talla
        if (!empty($params['size'])) {
            $query->whereHas('variants', fn($q) =>
                $q->where('size', $params['size'])
                  ->where('stock', '>', 0)
            );
        }

        // Color
        if (!empty($params['color'])) {
            $query->whereHas('variants', fn($q) =>
                $q->where('color', 'LIKE', "%{$params['color']}%")
            );
        }

        // Rango de precio
        if (!empty($params['min_price'])) {
            $query->where('price', '>=', $params['min_price']);
        }
        if (!empty($params['max_price'])) {
            $query->where('price', '<=', $params['max_price']);
        }

        // Solo con stock
        if (!empty($params['in_stock'])) {
            $query->whereHas('variants', fn($q) => $q->where('stock', '>', 0));
        }

        // Nuevos llegados (últimos 30 días)
        if (!empty($params['new_arrivals'])) {
            $query->where('created_at', '>=', now()->subDays(30));
        }

        $limit = setting('wa_bot_max_products') ?: 5;

        return $query->orderBy('is_featured', 'desc')
                     ->orderBy('created_at', 'desc')
                     ->limit($limit)
                     ->get();
    }

    /**
     * Detalle completo de un producto.
     */
    public function getProductDetail(int $productId): ?Product
    {
        return Product::with(['category', 'images', 'variants'])
            ->where('status', 'active')
            ->find($productId);
    }

    /**
     * Verificar disponibilidad de talla/color específico.
     */
    public function checkAvailability(int $productId, ?string $size = null, ?string $color = null): array
    {
        $query = ProductVariant::where('product_id', $productId);

        if ($size) {
            $query->where('size', $size);
        }
        if ($color) {
            $query->where('color', 'LIKE', "%{$color}%");
        }

        $variants = $query->get();

        return [
            'available'       => $variants->where('stock', '>', 0)->isNotEmpty(),
            'variants'        => $variants->map(fn($v) => [
                'size'  => $v->size,
                'color' => $v->color,
                'stock' => $v->stock,
                'price' => $v->price ?? $v->product->price,
            ])->toArray(),
            'available_sizes' => $variants->where('stock', '>', 0)->pluck('size')->unique()->values()->toArray(),
        ];
    }

    /**
     * Buscar por descripción visual (para búsqueda por imagen).
     * Tokeniza la descripción y busca productos con matches parciales.
     */
    public function searchByVisualDescription(string $description): Collection
    {
        $keywords = $this->extractSearchTerms($description);

        $query = Product::query()
            ->where('status', 'active')
            ->with(['category', 'images', 'variants']);

        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $q->orWhere('name', 'LIKE', "%{$keyword}%")
                  ->orWhere('description', 'LIKE', "%{$keyword}%")
                  ->orWhere('tags', 'LIKE', "%{$keyword}%");
            }
        });

        return $query->limit(5)->get();
    }

    /**
     * Extraer términos de búsqueda relevantes de una descripción visual.
     */
    protected function extractSearchTerms(string $description): array
    {
        $stopWords = ['de', 'en', 'con', 'sin', 'para', 'por', 'el', 'la', 'los', 'las', 'un', 'una', 'tipo', 'estilo'];
        $words = preg_split('/[\s,]+/', mb_strtolower($description));
        $words = array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stopWords));

        return array_values(array_unique($words));
    }
}
```

---

## 6. Configuración desde la UI del Admin

### 6.1 Settings keys

| Key | Tipo | Default | Descripción |
|---|---|---|---|
| `wa_bot_enabled` | boolean | false | Habilitar bot |
| `wa_bot_api_key` | string | — | API key de Anthropic |
| `wa_bot_llm_model` | select | `claude-haiku-4-5-20251001` | Modelo |
| `wa_bot_store_name` | string | — | Nombre de la tienda |
| `wa_bot_training_profile` | textarea | — | Perfil de comunicación generado |
| `wa_bot_custom_rules` | textarea | — | Reglas adicionales del negocio |
| `wa_bot_system_prompt` | textarea | — | System prompt completo (override) |
| `wa_bot_max_products` | number | 5 | Productos por respuesta |
| `wa_bot_activation_mode` | select | `delayed` | `immediate`, `delayed`, `manual` |
| `wa_bot_delay_minutes` | number | 30 | Minutos de espera antes de activar |
| `wa_bot_business_hours_start` | time | 08:00 | Inicio horario laboral |
| `wa_bot_business_hours_end` | time | 18:00 | Fin horario laboral |
| `wa_bot_instant_outside_hours` | boolean | true | Respuesta inmediata fuera de horario |
| `wa_bot_welcome_message` | string | — | Mensaje fallback si no hay API key |
| `wa_bot_order_instructions` | textarea | — | Instrucciones de cómo hacer pedido |
| `wa_meta_phone_id` | string | — | Phone Number ID de WhatsApp Business |
| `wa_meta_access_token` | string | — | Page Access Token |
| `wa_meta_app_id` | string | — | App ID de Meta |
| `wa_meta_app_secret` | string | — | App Secret |
| `wa_meta_verify_token` | string | auto | Verify Token del webhook |

### 6.2 Pantalla de configuración

```
Sección: Bot de WhatsApp con IA
├── Card: Conexión Meta
│   ├── Input: App ID
│   ├── Input: App Secret
│   ├── Input: Phone Number ID
│   ├── Input: Page Access Token
│   ├── Read-only: Webhook URL (con botón copiar)
│   └── Read-only: Verify Token
│
├── Card: Inteligencia Artificial
│   ├── Toggle: Activar Bot IA
│   ├── Input: API Key de Anthropic
│   ├── Select: Modelo (Haiku 4.5 / Sonnet 5)
│   ├── Input: Nombre de la tienda
│   └── Number: Máx productos por respuesta (1-10)
│
├── Card: Entrenamiento (con screenshots)
│   ├── Upload: Subir screenshots de conversaciones
│   ├── Botón: Generar perfil de comunicación
│   ├── Textarea: Perfil generado (editable)
│   └── Botón: Guardar perfil
│
├── Card: Activación del bot
│   ├── Radio: Siempre activo / Después de X min / Manual
│   ├── Number: Minutos de espera (default 30)
│   ├── Toggle: Respuesta inmediata fuera de horario
│   └── Time: Horario laboral (inicio / fin)
│
├── Card: Reglas del negocio
│   ├── Textarea: Reglas adicionales (envío, devoluciones, pagos)
│   ├── Textarea: Instrucciones de pedido
│   └── Textarea: System prompt avanzado (override)
│
└── Botón: Guardar + Botón: Probar conexión
```

---

## 7. Migraciones de base de datos

```php
// Tabla de conversaciones (misma estructura que Inmobiliaria)
Schema::create('whatsapp_conversations', function (Blueprint $table) {
    $table->id();
    $table->string('phone', 20)->index();
    $table->string('direction', 10);          // 'inbound' | 'outbound'
    $table->text('message');
    $table->boolean('is_human')->default(false); // true si respondió un humano
    $table->json('metadata')->nullable();
    $table->timestamps();

    $table->index(['phone', 'created_at']);
});

// Tabla de screenshots de entrenamiento
Schema::create('wa_bot_training_screenshots', function (Blueprint $table) {
    $table->id();
    $table->string('file_path');
    $table->text('analysis')->nullable();     // Análisis de Claude Vision
    $table->boolean('analyzed')->default(false);
    $table->timestamps();
});

// Tabla de sesiones de bot (para tracking de regla de 30 min)
Schema::create('wa_bot_sessions', function (Blueprint $table) {
    $table->id();
    $table->string('phone', 20)->index();
    $table->boolean('bot_active')->default(false);
    $table->timestamp('human_last_response_at')->nullable();
    $table->timestamp('bot_activated_at')->nullable();
    $table->timestamps();

    $table->index(['phone', 'bot_active']);
});
```

---

## 8. Rutas

```php
// Webhook público (sin auth)
Route::get('webhook/whatsapp', 'WhatsAppWebhookController@verify');
Route::post('webhook/whatsapp', 'WhatsAppWebhookController@handle');

// Admin — Configuración
Route::prefix('admin/whatsapp-bot')->middleware('auth')->group(function () {
    Route::get('settings', 'WhatsAppBotController@settings');
    Route::post('settings', 'WhatsAppBotController@saveSettings');
    Route::post('test-connection', 'WhatsAppBotController@testConnection');

    // Entrenamiento
    Route::post('training/upload', 'WhatsAppBotController@uploadScreenshots');
    Route::post('training/analyze', 'WhatsAppBotController@analyzeScreenshots');
    Route::post('training/save-profile', 'WhatsAppBotController@saveProfile');

    // Dashboard de conversaciones
    Route::get('conversations', 'WhatsAppBotController@conversations');
    Route::get('conversations/{phone}', 'WhatsAppBotController@conversationDetail');

    // Intervención humana
    Route::post('conversations/{phone}/reply', 'WhatsAppBotController@humanReply');
    Route::post('conversations/{phone}/take-over', 'WhatsAppBotController@takeOver');
});
```

---

## 9. Costo estimado

### Modelo recomendado: Claude Haiku 4.5

| Concepto | Detalle |
|---|---|
| Input tokens | $1 por 1M tokens |
| Output tokens | $5 por 1M tokens |
| Tokens por conversación (promedio) | ~2,000 input + ~400 output |
| Costo por conversación | ~$0.004 (~₡2) |

### Uso estimado mensual:

| Escenario | Conversaciones/mes | Costo Claude | Costo total |
|---|---|---|---|
| Bajo (10-20 chats/día) | ~500 | $2.00 | ~₡1,000 |
| Medio (30-50 chats/día) | ~1,200 | $4.80 | ~₡2,400 |
| Alto (100+ chats/día) | ~3,000 | $12.00 | ~₡6,000 |

### Costo de análisis de imágenes (Claude Vision):

| Tipo | Tokens/imagen | Costo por imagen |
|---|---|---|
| Imagen de cliente (búsqueda) | ~1,600 tokens | ~$0.002 |
| Screenshots entrenamiento | ~1,600 tokens × N | ~$0.002 × N (una sola vez) |

### Costo anual estimado: **$25 - $60 USD** (uso bajo/medio con Haiku 4.5)

Si se quiere mejor calidad de análisis de imágenes, usar **Sonnet 5** solo para el análisis visual y Haiku para las conversaciones regulares.

---

## 10. Intervención humana desde el Admin

### Panel de conversaciones en tiempo real:

```
┌─ Conversaciones Activas ─────────────────────────────────────┐
│                                                               │
│  🔍 Buscar por teléfono o nombre...                          │
│                                                               │
│  ┌─────────────────────────────────────────────────────────┐  │
│  │ 📱 +506 8888-1234 — María López                        │  │
│  │ 🤖 Bot activo (desde hace 12 min)                      │  │
│  │ Último msg: "¿Tienen en talla L?"                      │  │
│  │ [Ver chat]  [🙋 Tomar control]                         │  │
│  ├─────────────────────────────────────────────────────────┤  │
│  │ 📱 +506 7777-5678 — Carlos Pérez                       │  │
│  │ ⏳ Esperando respuesta (22 min — bot en 8 min)         │  │
│  │ Último msg: "Cuánto cuesta el vestido rojo?"           │  │
│  │ [Ver chat]  [✍️ Responder]  [🤖 Activar bot ahora]    │  │
│  ├─────────────────────────────────────────────────────────┤  │
│  │ 📱 +506 6666-9012 — Ana Ramírez                        │  │
│  │ 👤 Atendida por humano                                  │  │
│  │ Último msg: "Perfecto, hago la transferencia"          │  │
│  │ [Ver chat]                                              │  │
│  └─────────────────────────────────────────────────────────┘  │
└───────────────────────────────────────────────────────────────┘
```

### "Tomar control" de un chat:

Cuando un humano hace click en "Tomar control":
1. Se marca `is_human = true` en las próximas respuestas
2. El bot se desactiva para ese chat
3. El humano puede responder directamente desde el admin (se envía vía WhatsApp API)
4. Si el humano deja de responder por 30 min nuevamente, el bot puede reactivarse

---

## 11. Checklist de implementación

- [ ] Crear `WhatsAppBotService` adaptado para E-commerce (basado en el de Inmobiliaria)
- [ ] Crear `ProductSearchService` con búsqueda por keyword, talla, color, precio, stock
- [ ] Crear `ConversationTrainingService` para análisis de screenshots con Claude Vision
- [ ] Implementar descarga de media de WhatsApp (imágenes del cliente)
- [ ] Implementar envío de imágenes a Claude Vision para búsqueda por foto
- [ ] Implementar tool `search_by_image` basado en descripción visual
- [ ] Implementar tool `check_availability` para verificar stock por talla/color
- [ ] Implementar regla de 30 minutos con Job delayed
- [ ] Crear pantalla de configuración del bot
- [ ] Crear pantalla de entrenamiento con screenshots
- [ ] Crear panel de conversaciones activas con opción "Tomar control"
- [ ] Crear migraciones para tablas de conversaciones, screenshots, sesiones
- [ ] Configurar webhook de WhatsApp en Meta Developer Console
- [ ] Probar flujo completo: texto → búsqueda → respuesta con fotos
- [ ] Probar flujo de imagen: cliente envía foto → IA busca similar → responde
- [ ] Probar regla de 30 min: esperar sin respuesta → bot se activa
- [ ] Probar "Tomar control" humano → bot se desactiva

# Integración de IA (Claude) en Módulo de Instagram - E-commerce

## Resumen

Integrar un modelo de IA (Anthropic Claude) en el módulo existente de publicaciones de Instagram del E-commerce. El modelo reemplazará/complementará Google Vision API para análisis de imágenes y generación de descripciones, y añadirá la capacidad de generar carruseles automáticamente agrupando productos por estilo/categoría.

---

## 1. Contexto del Proyecto

### Módulo actual de Instagram
- El sistema E-commerce tiene un módulo que permite crear publicaciones (carruseles) para Instagram desde la UI del admin
- Actualmente usa **Google Vision API** para leer imágenes y generar descripciones básicas (poco exactas)
- Tiene plantillas de descripciones guardadas que se usan para generar textos aleatorios
- Los carruseles son de hasta 10 imágenes
- Se publican entre 10 y 20 posts por mes

### Referencia técnica: Bot de WhatsApp (proyecto Inmobiliaria)
En el proyecto hermano (CRM Inmobiliaria), se integró Claude exitosamente para un bot de WhatsApp. La integración usa:
- **HTTP directo** a `https://api.anthropic.com/v1/messages` (sin SDK)
- **Modelo**: `claude-haiku-4-5-20251001` (configurable desde settings)
- **Vision nativa**: Claude puede analizar imágenes directamente sin necesidad de Google Vision API como intermediario
- **Tool use**: El modelo puede llamar funciones (búsqueda de propiedades, detalles, etc.)
- **Configuración desde UI**: API key, modelo, system prompt, todo se guarda con el facade `Setting`

---

## 2. Arquitectura de la Integración

### 2.1 Servicio principal: `ClaudeVisionService`

Crear un servicio similar a la integración del bot de WhatsApp:

```php
<?php

namespace App\Services; // Ajustar namespace según el proyecto

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeVisionService
{
    /**
     * Analizar imágenes y generar descripción para un post de Instagram.
     *
     * @param array $imageUrls URLs públicas de las imágenes del carrusel
     * @param array $productContext Contexto de los productos (nombre, categoría, precio, etc.)
     * @param string|null $customInstructions Instrucciones adicionales del usuario
     * @return array{description: string, hashtags: string, alt_texts: array}
     */
    public function generatePostDescription(array $imageUrls, array $productContext = [], ?string $customInstructions = null): array
    {
        $apiKey = setting('instagram_ai_api_key');
        $model = setting('instagram_ai_model') ?: 'claude-sonnet-5';

        if (!$apiKey) {
            throw new \RuntimeException('API key de Claude no configurada.');
        }

        $systemPrompt = $this->buildSystemPrompt($customInstructions);
        $content = $this->buildMessageContent($imageUrls, $productContext);

        $response = Http::timeout(60)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 2048,
                'system' => $systemPrompt,
                'messages' => [
                    ['role' => 'user', 'content' => $content],
                ],
            ]);

        if (!$response->successful()) {
            Log::error('Claude Vision API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Error al comunicarse con Claude: ' . $response->status());
        }

        return $this->parseResponse($response->json());
    }

    /**
     * Analizar un catálogo de productos y agruparlos en carruseles automáticos.
     *
     * @param array $products Lista de productos con sus imágenes
     * @return array Lista de grupos sugeridos para carruseles
     */
    public function generateAutoCarousels(array $products): array
    {
        $apiKey = setting('instagram_ai_api_key');
        $model = setting('instagram_ai_model') ?: 'claude-sonnet-5';

        // Enviar imágenes representativas (1 por producto) + metadata
        // Claude agrupa por estilo, color, categoría, temporada, etc.
        $content = $this->buildGroupingContent($products);

        $response = Http::timeout(90)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 4096,
                'system' => $this->buildGroupingSystemPrompt(),
                'messages' => [
                    ['role' => 'user', 'content' => $content],
                ],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Error en agrupación automática: ' . $response->status());
        }

        return $this->parseGroupingResponse($response->json());
    }

    protected function buildMessageContent(array $imageUrls, array $productContext): array
    {
        $content = [];

        // Agregar cada imagen como bloque de imagen
        foreach ($imageUrls as $index => $url) {
            $content[] = [
                'type' => 'image',
                'source' => [
                    'type' => 'url',  // O 'base64' si las imágenes son locales
                    'url' => $url,
                ],
            ];
        }

        // Agregar contexto de productos como texto
        $contextText = "Información de los productos en las imágenes:\n";
        foreach ($productContext as $product) {
            $contextText .= "- {$product['name']}";
            if (!empty($product['category'])) $contextText .= " | Categoría: {$product['category']}";
            if (!empty($product['price'])) $contextText .= " | Precio: {$product['price']}";
            if (!empty($product['colors'])) $contextText .= " | Colores: {$product['colors']}";
            if (!empty($product['material'])) $contextText .= " | Material: {$product['material']}";
            $contextText .= "\n";
        }

        $content[] = ['type' => 'text', 'text' => $contextText];

        return $content;
    }

    protected function buildSystemPrompt(?string $customInstructions = null): string
    {
        $brandVoice = setting('instagram_ai_brand_voice') ?: '';
        $language = setting('instagram_ai_language') ?: 'es';
        $targetAudience = setting('instagram_ai_target_audience') ?: '';
        $customPrompt = setting('instagram_ai_system_prompt') ?: '';

        if ($customPrompt) {
            return $customPrompt;
        }

        $prompt = <<<PROMPT
Sos un experto en marketing digital y copywriting para Instagram, especializado en moda y e-commerce.

Tu trabajo es analizar las imágenes de productos y generar contenido optimizado para Instagram.

Reglas:
- Respondé en JSON con esta estructura exacta:
  {
    "description": "El caption completo del post",
    "hashtags": "Los hashtags separados por espacio",
    "alt_texts": ["Texto alternativo para cada imagen"],
    "suggested_title": "Título corto interno para identificar el post"
  }
- El caption debe ser atractivo, usar emojis moderados, incluir call-to-action.
- Máximo 2200 caracteres para el caption (límite de Instagram).
- Generá entre 15-25 hashtags relevantes, mezclando populares y de nicho.
- Los alt_texts deben ser descriptivos para accesibilidad (máx 125 caracteres c/u).
- Analizá las imágenes con detalle: colores, estilos, materiales, ocasión de uso, temporada.
- Si hay múltiples productos, encontrá el hilo conductor (estilo, color, ocasión) y usalo en el caption.
PROMPT;

        if ($brandVoice) {
            $prompt .= "\n\nVoz de marca: {$brandVoice}";
        }
        if ($targetAudience) {
            $prompt .= "\nPúblico objetivo: {$targetAudience}";
        }
        if ($customInstructions) {
            $prompt .= "\n\nInstrucciones adicionales del usuario: {$customInstructions}";
        }

        return $prompt;
    }

    protected function buildGroupingSystemPrompt(): string
    {
        return <<<PROMPT
Sos un experto en visual merchandising y marketing de moda para Instagram.

Tu trabajo es analizar un catálogo de productos y agruparlos en carruseles de Instagram que sean visualmente coherentes y atractivos.

Criterios de agrupación:
1. **Por estilo**: productos del mismo estilo (casual, formal, deportivo, etc.) juntos
2. **Por color/paleta**: productos con paleta de colores similar o complementaria
3. **Por ocasión**: productos para la misma ocasión (oficina, playa, fiesta, etc.)
4. **Por colección/temporada**: si los productos pertenecen a la misma línea
5. **Por outfit/look completo**: combinar prendas que forman un outfit

Reglas:
- Cada carrusel debe tener entre 3 y 10 imágenes.
- Respondé en JSON con esta estructura:
  {
    "carousels": [
      {
        "title": "Nombre interno del carrusel",
        "theme": "Tema/hilo conductor",
        "product_ids": [1, 5, 8, 12],
        "description": "Caption sugerido para Instagram",
        "hashtags": "hashtags sugeridos",
        "reasoning": "Por qué estos productos van juntos"
      }
    ],
    "unmatched_products": [3, 7],
    "suggestions": "Sugerencias generales para mejorar el contenido"
  }
- Un producto puede aparecer en máximo 2 carruseles diferentes.
- Priorizá la coherencia visual sobre la cantidad.
PROMPT;
    }

    protected function parseResponse(array $data): array
    {
        $text = '';
        foreach ($data['content'] ?? [] as $block) {
            if ($block['type'] === 'text') {
                $text .= $block['text'];
            }
        }

        // Intentar parsear como JSON
        $json = json_decode($text, true);
        if ($json && isset($json['description'])) {
            return $json;
        }

        // Fallback: devolver el texto raw
        return [
            'description' => $text,
            'hashtags' => '',
            'alt_texts' => [],
            'suggested_title' => '',
        ];
    }

    protected function parseGroupingResponse(array $data): array
    {
        $text = '';
        foreach ($data['content'] ?? [] as $block) {
            if ($block['type'] === 'text') {
                $text .= $block['text'];
            }
        }

        $json = json_decode($text, true);
        return $json ?: ['carousels' => [], 'unmatched_products' => [], 'suggestions' => $text];
    }
}
```

### 2.2 Configuración desde la UI

Crear una sección de configuración en el admin con los siguientes campos. **Usar el estilo de `admin-ui.css`** existente del proyecto.

#### Settings keys a crear:

| Key | Tipo | Default | Descripción |
|---|---|---|---|
| `instagram_ai_enabled` | boolean | false | Habilitar/deshabilitar IA |
| `instagram_ai_api_key` | string | — | API key de Anthropic |
| `instagram_ai_model` | select | `claude-sonnet-5` | Modelo a usar |
| `instagram_ai_system_prompt` | textarea | — | System prompt personalizado (override) |
| `instagram_ai_brand_voice` | textarea | — | Descripción de la voz de marca |
| `instagram_ai_target_audience` | string | — | Público objetivo |
| `instagram_ai_language` | select | `es` | Idioma de las descripciones |
| `instagram_ai_auto_hashtags` | boolean | true | Generar hashtags automáticamente |
| `instagram_ai_max_hashtags` | number | 20 | Máximo de hashtags |

#### Modelos disponibles (dropdown):

```
claude-haiku-4-5-20251001  →  "Haiku 4.5 — Rápido y económico ($1/$5 por 1M tokens)"
claude-sonnet-5            →  "Sonnet 5 — Mejor balance calidad/precio ($3/$15 por 1M tokens)" [Recomendado]
claude-opus-4-8            →  "Opus 4.8 — Máxima calidad ($5/$25 por 1M tokens)"
```

#### Pantalla de configuración (usar admin-ui.css):

```
Sección: Configuración de IA para Instagram
├── Card: Credenciales
│   ├── Toggle: Habilitar análisis con IA
│   ├── Input: API Key de Anthropic
│   ├── Select: Modelo (Haiku/Sonnet/Opus)
│   └── Nota informativa: costo estimado según modelo seleccionado
│
├── Card: Personalización
│   ├── Textarea: Voz de marca (ej: "Juvenil, fresca, inclusiva")
│   ├── Input: Público objetivo (ej: "Mujeres 18-35, Costa Rica")
│   ├── Select: Idioma (Español, Inglés, Portugués)
│   ├── Toggle: Generar hashtags automáticos
│   └── Number: Máximo de hashtags
│
├── Card: System Prompt (avanzado, colapsable)
│   ├── Textarea: System prompt personalizado
│   └── Nota: "Dejá vacío para usar el prompt predeterminado"
│
└── Botón: Guardar configuración + Botón: Probar conexión
```

---

## 3. Modificaciones al Módulo de Carruseles Existente

### 3.1 Botones de análisis por imagen

En la pantalla de creación/edición de carrusel, junto a cada imagen, agregar dos botones:

```
[Imagen del producto]
┌──────────────────────────────────┐
│  📷 Imagen 1 de 10              │
│  producto-rojo-vestido.jpg       │
│                                  │
│  [🔍 Google Vision]  [🤖 IA]   │  ← Botones lado a lado
│                                  │
│  Descripción generada:           │
│  ┌────────────────────────────┐  │
│  │ Vestido rojo de corte A... │  │
│  └────────────────────────────┘  │
└──────────────────────────────────┘
```

- **Botón "Google Vision"**: mantiene la funcionalidad actual (análisis básico)
- **Botón "IA" (Claude)**: envía la imagen a Claude para análisis detallado — devuelve descripción más precisa de la prenda (material, estilo, color exacto, ocasión de uso, detalles de diseño)

### 3.2 Generación de descripción del carrusel completo

Agregar un botón en la parte superior del formulario de carrusel:

```
┌─ Nuevo Carrusel ─────────────────────────────────────┐
│                                                       │
│  [🤖 Generar descripción con IA]                     │  ← Analiza TODAS las imágenes juntas
│  [📝 Generar desde plantilla]        (ya existe)     │
│                                                       │
│  Descripción del post:                                │
│  ┌─────────────────────────────────────────────────┐  │
│  │ ✨ Nueva colección que enamora ✨               │  │
│  │                                                 │  │
│  │ Descubrí los tonos que marcan esta temporada... │  │
│  └─────────────────────────────────────────────────┘  │
│                                                       │
│  Hashtags:                                            │
│  ┌─────────────────────────────────────────────────┐  │
│  │ #moda #fashion #nuevacoleccion #outfit ...      │  │
│  └─────────────────────────────────────────────────┘  │
└───────────────────────────────────────────────────────┘
```

El botón "Generar descripción con IA":
1. Toma todas las imágenes del carrusel
2. Toma la metadata de los productos asociados (nombre, categoría, precio)
3. Envía todo a Claude en UNA sola llamada
4. Claude analiza las imágenes en conjunto, encuentra el hilo conductor
5. Genera: caption, hashtags, y alt_text por imagen
6. Rellena los campos del formulario automáticamente (el usuario puede editar antes de publicar)

### 3.3 Comparativa de resultados

Considerar mostrar una comparativa cuando ambos métodos están disponibles:

```
┌─ Análisis de Imagen ───────────────────────────┐
│                                                 │
│  Google Vision:                                 │
│  "Clothing, Red, Dress, Fashion"                │
│                                                 │
│  Claude IA:                                     │
│  "Vestido rojo de corte A en tela satinada,     │
│   con escote en V y mangas abullonadas.         │
│   Ideal para ocasiones formales o cenas          │
│   elegantes. Largo midi, ajuste en cintura."    │
│                                                 │
│  [Usar Google Vision]  [Usar IA] ← seleccionar  │
└─────────────────────────────────────────────────┘
```

---

## 4. Generación Automática de Carruseles

### 4.1 Funcionalidad principal

Agregar una nueva sección/pantalla: **"Generador automático de carruseles"**

```
┌─ Generador Automático de Carruseles ─────────────────────────┐
│                                                               │
│  La IA analizará tus productos disponibles y creará          │
│  carruseles agrupados por estilo, color y ocasión.           │
│                                                               │
│  Filtros (opcionales):                                        │
│  ┌─────────────┐ ┌──────────────┐ ┌───────────────────────┐  │
│  │ Categoría ▼ │ │ Temporada  ▼ │ │ Solo sin publicar  ☑ │  │
│  └─────────────┘ └──────────────┘ └───────────────────────┘  │
│                                                               │
│  Productos seleccionados: 45 de 120 disponibles              │
│                                                               │
│  [🤖 Generar carruseles automáticos]                         │
│                                                               │
│  ═══════════════════════════════════════════════              │
│  Resultado: 6 carruseles sugeridos                           │
│                                                               │
│  ┌─ Carrusel 1: "Look Casual de Verano" ─────────────────┐  │
│  │ 🎯 Tema: Outfits casuales en tonos pastel             │  │
│  │ 📸 8 imágenes                                          │  │
│  │ 📝 "Frescura que se lleva puesta ☀️ Descubrí..."     │  │
│  │ 💡 Razón: Productos con paleta similar, estilo casual │  │
│  │                                                        │  │
│  │ [Vista previa]  [Editar]  [✅ Crear post]  [❌ Omitir]│  │
│  └────────────────────────────────────────────────────────┘  │
│                                                               │
│  ┌─ Carrusel 2: "Elegancia para la Noche" ───────────────┐  │
│  │ ...                                                    │  │
│  └────────────────────────────────────────────────────────┘  │
│                                                               │
│  [📤 Crear todos los posts seleccionados]                    │
└───────────────────────────────────────────────────────────────┘
```

### 4.2 Flujo del generador automático

```
1. Usuario selecciona productos (o usa filtros)
       │
       ▼
2. Se envían a Claude: 1 imagen representativa por producto + metadata
   (Para 45 productos ≈ 45 imágenes × ~1,600 tokens = ~72,000 input tokens)
       │
       ▼
3. Claude analiza y agrupa por:
   - Estilo visual (casual, formal, deportivo)
   - Paleta de colores (similares o complementarios)
   - Ocasión de uso (oficina, playa, fiesta)
   - Tipo de prenda (si forman outfits completos)
       │
       ▼
4. Devuelve JSON con grupos sugeridos + caption + hashtags por grupo
       │
       ▼
5. UI muestra preview de cada carrusel sugerido
   - El usuario puede: editar, aprobar, descartar, reorganizar
       │
       ▼
6. Al aprobar → se crea el post como borrador (o se publica directo)
```

### 4.3 Consideraciones de costo para generación automática

Si se envían 45 productos (1 imagen c/u) para agrupación:
- Input: ~72,000 tokens (imágenes) + ~5,000 (metadata + prompt) = ~77,000 tokens
- Output: ~2,000 tokens (JSON con grupos)
- **Costo con Sonnet 5**: ~$0.26 por ejecución
- Con 2-3 ejecuciones al mes: **< $1 USD/mes**

---

## 5. Implementación técnica

### 5.1 Rutas sugeridas

```php
// Configuración de IA
Route::get('instagram/ai-settings', 'InstagramAiController@settings');
Route::post('instagram/ai-settings', 'InstagramAiController@saveSettings');
Route::post('instagram/ai-settings/test', 'InstagramAiController@testConnection');

// Análisis de imagen individual
Route::post('instagram/ai/analyze-image', 'InstagramAiController@analyzeImage');

// Generación de descripción para carrusel
Route::post('instagram/ai/generate-description', 'InstagramAiController@generateDescription');

// Generador automático de carruseles
Route::get('instagram/ai/auto-generate', 'InstagramAiController@autoGenerateForm');
Route::post('instagram/ai/auto-generate', 'InstagramAiController@autoGenerate');
Route::post('instagram/ai/auto-generate/create-posts', 'InstagramAiController@createPostsFromSuggestions');
```

### 5.2 Controller: `InstagramAiController`

```php
class InstagramAiController extends Controller
{
    // Pantalla de configuración (API key, modelo, brand voice)
    public function settings() { ... }
    public function saveSettings(Request $request) { ... }

    // Probar conexión con la API
    public function testConnection()
    {
        // Envía un mensaje simple a Claude para verificar que la API key funciona
        // Devuelve JSON { success: true/false, model: "...", message: "..." }
    }

    // Analizar una imagen individual (botón "IA" junto a cada imagen)
    public function analyzeImage(Request $request)
    {
        // Recibe: image_url o image_base64
        // Devuelve: { description: "...", details: { color, style, material, occasion } }
    }

    // Generar descripción completa del carrusel (botón "Generar con IA")
    public function generateDescription(Request $request)
    {
        // Recibe: image_urls[], product_context[], custom_instructions?
        // Devuelve: { description, hashtags, alt_texts[], suggested_title }
    }

    // Pantalla del generador automático
    public function autoGenerateForm() { ... }

    // Ejecutar la agrupación automática
    public function autoGenerate(Request $request)
    {
        // Recibe: product_ids[] o filtros (category, season, etc.)
        // Devuelve: { carousels: [...], unmatched_products: [...] }
    }

    // Crear los posts aprobados por el usuario
    public function createPostsFromSuggestions(Request $request)
    {
        // Recibe: carousels[] (los aprobados/editados por el usuario)
        // Crea cada carrusel como post (borrador o publicado según config)
    }
}
```

### 5.3 Migración sugerida

```php
// Si se necesita trackear el uso de IA por post
Schema::table('instagram_posts', function (Blueprint $table) {  // Ajustar nombre de tabla
    $table->boolean('ai_generated')->default(false)->after('status');
    $table->string('ai_model', 50)->nullable()->after('ai_generated');
    $table->json('ai_metadata')->nullable()->after('ai_model');  // Almacena el análisis completo
});
```

### 5.4 JavaScript del frontend (AJAX)

```javascript
// Botón "IA" junto a cada imagen
document.querySelectorAll('.btn-ai-analyze').forEach(btn => {
    btn.addEventListener('click', async function() {
        const imageUrl = this.dataset.imageUrl;
        const resultContainer = this.closest('.image-card').querySelector('.ai-result');

        this.disabled = true;
        this.innerHTML = '<span class="spinner"></span> Analizando...';

        try {
            const response = await fetch('/admin/instagram/ai/analyze-image', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ image_url: imageUrl }),
            });

            const data = await response.json();
            if (data.description) {
                resultContainer.textContent = data.description;
            }
        } catch (error) {
            alert('Error al analizar la imagen');
        } finally {
            this.disabled = false;
            this.innerHTML = '🤖 IA';
        }
    });
});

// Botón "Generar descripción con IA" para todo el carrusel
document.getElementById('btn-ai-generate-caption').addEventListener('click', async function() {
    const images = collectAllCarouselImages(); // URLs de las imágenes del carrusel
    const products = collectProductContext();   // Metadata de los productos

    this.disabled = true;
    this.innerHTML = '<span class="spinner"></span> Generando descripción...';

    try {
        const response = await fetch('/admin/instagram/ai/generate-description', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ image_urls: images, product_context: products }),
        });

        const data = await response.json();

        // Rellenar campos del formulario
        document.getElementById('post-caption').value = data.description || '';
        document.getElementById('post-hashtags').value = data.hashtags || '';

        // Alt texts por imagen
        if (data.alt_texts) {
            data.alt_texts.forEach((alt, index) => {
                const input = document.getElementById(`alt-text-${index}`);
                if (input) input.value = alt;
            });
        }
    } catch (error) {
        alert('Error al generar descripción');
    } finally {
        this.disabled = false;
        this.innerHTML = '🤖 Generar descripción con IA';
    }
});
```

---

## 6. Envío de imágenes a Claude

### Opción A: Por URL (recomendada si las imágenes son públicas)

```php
$content[] = [
    'type' => 'image',
    'source' => [
        'type' => 'url',
        'url' => 'https://tudominio.com/storage/products/image.jpg',
    ],
];
```

### Opción B: Por Base64 (si las imágenes son privadas/locales)

```php
$imageData = base64_encode(file_get_contents($imagePath));
$mimeType = mime_content_type($imagePath);

$content[] = [
    'type' => 'image',
    'source' => [
        'type' => 'base64',
        'media_type' => $mimeType,  // image/jpeg, image/png, image/webp
        'data' => $imageData,
    ],
];
```

### Límites de Claude para imágenes:
- Máximo **20 imágenes por request** (suficiente para carruseles de 10)
- Formatos soportados: JPEG, PNG, GIF, WebP
- Tamaño máximo por imagen: 20MB
- Resolución: se redimensiona internamente a max 1568px en el lado más largo
- Tokens por imagen ≈ (ancho × alto) / 750

---

## 7. Estilos (admin-ui.css)

Todas las pantallas nuevas deben usar las clases CSS existentes del proyecto. Ejemplos de clases a reutilizar:

```html
<!-- Usar las mismas clases que el resto del admin -->
<div class="card">                          <!-- Card container -->
<div class="card-header">                   <!-- Card header -->
<div class="card-body">                     <!-- Card body -->
<div class="form-group">                    <!-- Form groups -->
<button class="btn btn-primary">            <!-- Botones primarios -->
<button class="btn btn-outline-secondary">  <!-- Botones secundarios -->
<div class="alert alert-info">              <!-- Alertas informativas -->
<div class="table-responsive">              <!-- Tablas responsive -->

<!-- Componentes específicos a crear (extender admin-ui.css) -->
.ai-badge { }             /* Badge "IA" junto al botón */
.ai-result-card { }       /* Card de resultado del análisis */
.ai-comparison { }        /* Vista de comparación Google Vision vs Claude */
.carousel-preview { }     /* Preview de carrusel sugerido */
.carousel-group-card { }  /* Card de grupo de carrusel automático */
```

**Nota importante**: Revisar el archivo `admin-ui.css` del proyecto E-commerce para identificar las clases exactas y variables CSS (colores, spacing, tipografía) antes de crear pantallas nuevas. Mantener consistencia visual con el resto del admin.

---

## 8. Sugerencias Adicionales

### 8.1 Historial de generaciones

Guardar cada generación de IA en una tabla de historial:
- Permite al usuario volver a usar descripciones anteriores
- Sirve como dataset para analizar qué tipo de contenido genera más engagement
- Permite ver el costo acumulado

```php
Schema::create('instagram_ai_generations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->nullable();    // Si se asoció a un post
    $table->string('model', 50);                  // Modelo usado
    $table->integer('input_tokens')->default(0);  // Tokens consumidos (input)
    $table->integer('output_tokens')->default(0); // Tokens consumidos (output)
    $table->json('images_analyzed');               // URLs de imágenes analizadas
    $table->text('prompt_used');                   // System prompt usado
    $table->json('result');                        // Resultado completo de Claude
    $table->string('type', 20);                   // 'single_image', 'carousel', 'auto_group'
    $table->timestamps();
});
```

### 8.2 Prompt templates guardables

Permitir al usuario crear y guardar templates de prompts:
- "Promoción de temporada" → prompt enfocado en urgencia y descuentos
- "Lanzamiento de colección" → prompt enfocado en novedad y exclusividad
- "Outfit del día" → prompt enfocado en combinación de prendas
- "Rebajas" → prompt enfocado en precios y ahorro

### 8.3 Programación de publicaciones generadas

Después de generar carruseles automáticos, permitir:
- Programar fecha/hora de publicación de cada uno
- Distribuir los posts uniformemente en el mes (ej: 20 posts → 1 cada 1.5 días)
- Vista de calendario con los posts programados

### 8.4 A/B Testing de descripciones

Para posts importantes, generar 2-3 variantes del caption:
- Tono casual vs formal
- Con precios vs sin precios
- Enfocado en producto vs enfocado en estilo de vida
- El usuario elige cuál publicar

Implementación: enviar el mismo set de imágenes con variaciones en el prompt (agregar un parámetro `tone: "casual" | "formal" | "aspirational"`), y mostrar las opciones lado a lado.

### 8.5 Análisis de rendimiento con IA

Una vez publicado, si se obtiene el engagement (likes, comments, shares) via Instagram API:
- Enviar las métricas a Claude junto con el caption usado
- Claude analiza qué tipo de contenido funciona mejor
- Genera recomendaciones: "Los posts con tono casual y emojis obtienen 30% más interacción"

### 8.6 Regeneración inteligente

Agregar un botón "Regenerar" que permite:
- Regenerar con instrucción específica: "Hacelo más corto", "Agregá llamado a la acción", "Cambiar tono a más juvenil"
- El historial de la conversación se mantiene (Claude sabe qué generó antes y qué feedback recibió)

### 8.7 Soporte multi-idioma

Si el E-commerce vende internacionalmente:
- Generar el caption en múltiples idiomas (una llamada por idioma o todo en una)
- Hashtags localizados por mercado
- El toggle de idioma ya está contemplado en la configuración

### 8.8 Integración con el calendario del CRM

Si en el futuro se conectan ambos proyectos:
- Los posts programados podrían aparecer en el calendario del CRM
- Las métricas de Instagram podrían alimentar el dashboard del CRM
- Los leads generados desde Instagram podrían fluir al CRM automáticamente

---

## 9. Costo estimado mensual

### Uso normal (10-20 posts/mes, análisis manual):

| Acción | Calls/mes | Tokens/call | Costo Sonnet 5 |
|---|---|---|---|
| Describir carrusel (10 imgs) | 10-20 | ~17,000 in + ~300 out | $0.56 - $1.11 |
| Analizar imagen individual | ~30 | ~2,100 in + ~200 out | $0.22 |
| **Total normal** | | | **< $1.50/mes** |

### Con generación automática:

| Acción | Calls/mes | Tokens/call | Costo Sonnet 5 |
|---|---|---|---|
| Agrupar 50 productos | 2-3 | ~82,000 in + ~2,000 out | $0.57 |
| Describir carruseles generados | 10-15 | ~17,000 in + ~300 out | $0.56 - $0.84 |
| **Total con auto** | | | **< $2.50/mes** |

### Costo anual estimado: **$18 - $30 USD** con Sonnet 5

---

## 10. Checklist de implementación

- [ ] Crear `ClaudeVisionService` con métodos para análisis y agrupación
- [ ] Crear pantalla de configuración de IA (API key, modelo, brand voice) usando `admin-ui.css`
- [ ] Agregar botón "IA" junto al botón "Google Vision" en cada imagen del carrusel
- [ ] Agregar botón "Generar descripción con IA" en el formulario de carrusel
- [ ] Crear pantalla de "Generador automático de carruseles"
- [ ] Crear migración para columnas `ai_generated`, `ai_model`, `ai_metadata` en tabla de posts
- [ ] Crear tabla `instagram_ai_generations` para historial
- [ ] Implementar AJAX en frontend para llamadas asíncronas
- [ ] Agregar ruta de test de conexión (validar API key)
- [ ] Pruebas con imágenes reales del catálogo
- [ ] Documentar en la UI cómo obtener API key de Anthropic

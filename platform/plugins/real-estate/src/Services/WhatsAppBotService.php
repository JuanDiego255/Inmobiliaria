<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Enums\CrmActivityTypeEnum;
use Botble\RealEstate\Enums\CrmLeadSourceEnum;
use Botble\RealEstate\Enums\CrmLeadStageEnum;
use Botble\RealEstate\Models\CrmActivity;
use Botble\RealEstate\Models\CrmLead;
use Botble\RealEstate\Models\WhatsAppConversation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppBotService
{
    protected PropertySearchService $searchService;

    public function __construct(PropertySearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function processIncomingMessage(string $from, string $name, string $message, array $metadata = []): void
    {
        if (! setting('crm_whatsapp_bot_enabled')) {
            return;
        }

        $lead = $this->getOrCreateLead($from, $name);

        WhatsAppConversation::query()->create([
            'phone' => $from,
            'direction' => 'inbound',
            'message' => mb_substr($message, 0, 5000),
            'lead_id' => $lead->id,
            'metadata' => $metadata,
        ]);

        $history = $this->getConversationContext($from);
        $reply = $this->callLLM($message, $history);

        WhatsAppConversation::query()->create([
            'phone' => $from,
            'direction' => 'outbound',
            'message' => $reply,
            'lead_id' => $lead->id,
        ]);

        $this->sendWhatsAppReply($from, $reply);

        CrmActivity::query()->create([
            'lead_id' => $lead->id,
            'type' => CrmActivityTypeEnum::META_AUTO,
            'description' => 'Bot WhatsApp respondió a: "' . mb_substr($message, 0, 150) . '"',
            'completed_at' => now(),
        ]);
    }

    protected function callLLM(string $message, array $history): string
    {
        $provider = setting('crm_whatsapp_bot_llm_provider') ?: 'claude';
        $apiKey = setting('crm_whatsapp_bot_llm_api_key');

        if (! $apiKey) {
            return setting('crm_whatsapp_bot_welcome_message')
                ?: '¡Hola! Gracias por contactarnos. Un agente te atenderá pronto.';
        }

        try {
            return match ($provider) {
                'openai' => $this->callOpenAI($message, $history, $apiKey),
                default => $this->callClaude($message, $history, $apiKey),
            };
        } catch (\Exception $e) {
            Log::error('WhatsAppBot LLM error', ['error' => $e->getMessage()]);

            return '¡Hola! Gracias por tu interés. Un agente te contactará pronto para ayudarte.';
        }
    }

    protected function callClaude(string $message, array $history, string $apiKey): string
    {
        $model = setting('crm_whatsapp_bot_llm_model') ?: 'claude-haiku-4-5-20251001';
        $systemPrompt = $this->buildSystemPrompt();

        $messages = [];
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['direction'] === 'inbound' ? 'user' : 'assistant',
                'content' => $msg['message'],
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        $tools = $this->getToolDefinitions();

        $response = Http::timeout(30)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 1024,
                'system' => $systemPrompt,
                'tools' => $tools,
                'messages' => $messages,
            ]);

        if (! $response->successful()) {
            Log::error('Claude API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('Claude API error: ' . $response->status());
        }

        $data = $response->json();

        return $this->processLLMResponse($data, $messages, $apiKey, $model, $systemPrompt, $tools);
    }

    protected function callOpenAI(string $message, array $history, string $apiKey): string
    {
        $model = setting('crm_whatsapp_bot_llm_model') ?: 'gpt-4o-mini';
        $systemPrompt = $this->buildSystemPrompt();

        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['direction'] === 'inbound' ? 'user' : 'assistant',
                'content' => $msg['message'],
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        $tools = $this->getOpenAIToolDefinitions();

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'max_tokens' => 1024,
                'messages' => $messages,
                'tools' => $tools,
            ]);

        if (! $response->successful()) {
            Log::error('OpenAI API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('OpenAI API error: ' . $response->status());
        }

        $data = $response->json();

        return $this->processOpenAIResponse($data, $messages, $apiKey, $model, $tools);
    }

    protected function processLLMResponse(array $data, array $messages, string $apiKey, string $model, string $systemPrompt, array $tools): string
    {
        $textParts = [];
        $toolUses = [];

        foreach ($data['content'] ?? [] as $block) {
            if ($block['type'] === 'text') {
                $textParts[] = $block['text'];
            } elseif ($block['type'] === 'tool_use') {
                $toolUses[] = $block;
            }
        }

        if (empty($toolUses)) {
            return implode("\n", $textParts) ?: '¡Hola! ¿En qué puedo ayudarte?';
        }

        $toolResults = [];
        foreach ($toolUses as $toolUse) {
            $result = $this->handleToolCall($toolUse['name'], $toolUse['input'] ?? []);
            $toolResults[] = [
                'type' => 'tool_result',
                'tool_use_id' => $toolUse['id'],
                'content' => $result,
            ];
        }

        $messages[] = ['role' => 'assistant', 'content' => $data['content']];
        $messages[] = ['role' => 'user', 'content' => $toolResults];

        $followUp = Http::timeout(30)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 1024,
                'system' => $systemPrompt,
                'tools' => $tools,
                'messages' => $messages,
            ]);

        if (! $followUp->successful()) {
            return implode("\n", $textParts) ?: 'Encontré algunas opciones, un agente te las compartirá pronto.';
        }

        $followUpData = $followUp->json();
        $finalText = [];
        foreach ($followUpData['content'] ?? [] as $block) {
            if ($block['type'] === 'text') {
                $finalText[] = $block['text'];
            }
        }

        return implode("\n", $finalText) ?: implode("\n", $textParts) ?: '¡Hola! ¿En qué puedo ayudarte?';
    }

    protected function processOpenAIResponse(array $data, array $messages, string $apiKey, string $model, array $tools): string
    {
        $choice = $data['choices'][0] ?? [];
        $assistantMessage = $choice['message'] ?? [];

        $toolCalls = $assistantMessage['tool_calls'] ?? [];

        if (empty($toolCalls)) {
            return $assistantMessage['content'] ?? '¡Hola! ¿En qué puedo ayudarte?';
        }

        $messages[] = $assistantMessage;

        foreach ($toolCalls as $toolCall) {
            $fn = $toolCall['function'] ?? [];
            $params = json_decode($fn['arguments'] ?? '{}', true) ?: [];
            $result = $this->handleToolCall($fn['name'] ?? '', $params);

            $messages[] = [
                'role' => 'tool',
                'tool_call_id' => $toolCall['id'],
                'content' => $result,
            ];
        }

        $followUp = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'max_tokens' => 1024,
                'messages' => $messages,
                'tools' => $tools,
            ]);

        if (! $followUp->successful()) {
            return $assistantMessage['content'] ?? 'Encontré opciones, un agente te las compartirá.';
        }

        $followUpData = $followUp->json();

        return $followUpData['choices'][0]['message']['content'] ?? '¡Hola! ¿En qué puedo ayudarte?';
    }

    protected function handleToolCall(string $tool, array $params): string
    {
        return match ($tool) {
            'search_properties' => $this->toolSearchProperties($params),
            'get_property_detail' => $this->toolGetPropertyDetail($params),
            default => 'Herramienta no reconocida.',
        };
    }

    protected function toolSearchProperties(array $params): string
    {
        $properties = $this->searchService->searchProperties($params);

        if ($properties->isEmpty()) {
            return json_encode([
                'results' => 0,
                'message' => 'No se encontraron propiedades con esos criterios.',
            ]);
        }

        $results = [];
        foreach ($properties as $property) {
            $currency = $property->currency;
            $symbol = $currency ? $currency->symbol : '$';

            $location = '';
            if ($property->city && $property->city->name) {
                $location = $property->city->name;
                if ($property->state && $property->state->name) {
                    $location .= ', ' . $property->state->name;
                }
            }

            $results[] = [
                'id' => $property->id,
                'name' => $property->name,
                'type' => $property->type->value,
                'price' => $symbol . number_format($property->price, 0),
                'location' => $location ?: $property->location,
                'bedrooms' => $property->number_bedroom,
                'bathrooms' => $property->number_bathroom,
                'square' => $property->square ? number_format($property->square) . ' ' . setting('real_estate_square_unit', 'm²') : null,
                'category' => $property->categories->first()?->name,
            ];
        }

        return json_encode(['results' => count($results), 'properties' => $results]);
    }

    protected function toolGetPropertyDetail(array $params): string
    {
        $propertyId = (int) ($params['property_id'] ?? 0);
        if (! $propertyId) {
            return json_encode(['error' => 'Se requiere property_id.']);
        }

        $property = $this->searchService->getPropertyDetail($propertyId);
        if (! $property) {
            return json_encode(['error' => 'Propiedad no encontrada.']);
        }

        $currency = $property->currency;
        $symbol = $currency ? $currency->symbol : '$';

        $location = '';
        if ($property->city && $property->city->name) {
            $location = $property->city->name;
            if ($property->state && $property->state->name) {
                $location .= ', ' . $property->state->name;
            }
        }

        return json_encode([
            'id' => $property->id,
            'name' => $property->name,
            'type' => $property->type->value,
            'price' => $symbol . number_format($property->price, 0),
            'location' => $location ?: $property->location,
            'bedrooms' => $property->number_bedroom,
            'bathrooms' => $property->number_bathroom,
            'square' => $property->square ? number_format($property->square) . ' ' . setting('real_estate_square_unit', 'm²') : null,
            'description' => $property->description ? mb_substr(strip_tags($property->description), 0, 500) : null,
            'features' => $property->features->pluck('name')->toArray(),
            'project' => $property->project?->name,
            'category' => $property->categories->first()?->name,
        ]);
    }

    protected function buildSystemPrompt(): string
    {
        $custom = setting('crm_whatsapp_bot_system_prompt');
        if ($custom) {
            return $custom;
        }

        return <<<'PROMPT'
Sos un asistente inmobiliario profesional y amigable. Tu trabajo es ayudar a clientes a encontrar propiedades según sus necesidades.

Reglas:
- Respondé siempre en español, de forma concisa y profesional.
- Usá la herramienta search_properties para buscar propiedades cuando el cliente pregunte por opciones.
- Usá get_property_detail cuando el cliente pida más información de una propiedad específica.
- Si no encontrás resultados, sugerí ampliar la búsqueda (otra zona, rango de precio más amplio, etc.).
- Si el cliente quiere agendar una visita o hablar con un agente, indicale que un asesor lo contactará pronto.
- No inventés propiedades. Solo mostrá las que devuelve la herramienta de búsqueda.
- Formateá las respuestas para WhatsApp: usá *negrita* para nombres y precios, emojis moderados.
- Sé breve — máximo 3-4 propiedades por mensaje para no saturar.
PROMPT;
    }

    protected function getToolDefinitions(): array
    {
        return [
            [
                'name' => 'search_properties',
                'description' => 'Buscar propiedades inmobiliarias disponibles según criterios del cliente.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'keyword' => ['type' => 'string', 'description' => 'Texto libre de búsqueda (nombre de proyecto, zona, etc.)'],
                        'type' => ['type' => 'string', 'enum' => ['sale', 'rent'], 'description' => 'Tipo: sale (venta) o rent (alquiler)'],
                        'min_price' => ['type' => 'number', 'description' => 'Precio mínimo'],
                        'max_price' => ['type' => 'number', 'description' => 'Precio máximo'],
                        'bedrooms' => ['type' => 'integer', 'description' => 'Número de habitaciones'],
                        'bathrooms' => ['type' => 'integer', 'description' => 'Número de baños'],
                        'location' => ['type' => 'string', 'description' => 'Ciudad, zona o ubicación'],
                        'category' => ['type' => 'string', 'description' => 'Tipo de propiedad: casa, apartamento, local, terreno, etc.'],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'get_property_detail',
                'description' => 'Obtener detalles completos de una propiedad específica por su ID.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'property_id' => ['type' => 'integer', 'description' => 'ID de la propiedad'],
                    ],
                    'required' => ['property_id'],
                ],
            ],
        ];
    }

    protected function getOpenAIToolDefinitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_properties',
                    'description' => 'Buscar propiedades inmobiliarias disponibles según criterios del cliente.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'keyword' => ['type' => 'string', 'description' => 'Texto libre de búsqueda'],
                            'type' => ['type' => 'string', 'enum' => ['sale', 'rent'], 'description' => 'sale (venta) o rent (alquiler)'],
                            'min_price' => ['type' => 'number', 'description' => 'Precio mínimo'],
                            'max_price' => ['type' => 'number', 'description' => 'Precio máximo'],
                            'bedrooms' => ['type' => 'integer', 'description' => 'Número de habitaciones'],
                            'bathrooms' => ['type' => 'integer', 'description' => 'Número de baños'],
                            'location' => ['type' => 'string', 'description' => 'Ciudad, zona o ubicación'],
                            'category' => ['type' => 'string', 'description' => 'Tipo de propiedad'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_property_detail',
                    'description' => 'Obtener detalles completos de una propiedad por su ID.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'property_id' => ['type' => 'integer', 'description' => 'ID de la propiedad'],
                        ],
                        'required' => ['property_id'],
                    ],
                ],
            ],
        ];
    }

    public function sendWhatsAppReply(string $to, string $message): void
    {
        $phoneNumberId = setting('crm_meta_whatsapp_phone_id');
        $token = setting('crm_meta_page_access_token');

        if (! $phoneNumberId || ! $token) {
            Log::warning('WhatsAppBot: Missing phone_number_id or access token for reply.');
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => mb_substr($message, 0, 4096)],
            ]);

            if (! $response->successful()) {
                Log::error('WhatsAppBot: Failed to send reply', [
                    'to' => $to,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('WhatsAppBot: Exception sending reply', ['error' => $e->getMessage()]);
        }
    }

    protected function getOrCreateLead(string $phone, string $name): CrmLead
    {
        $normalized = preg_replace('/[^0-9+]/', '', $phone);

        $lead = CrmLead::query()
            ->where(function ($q) use ($normalized, $phone) {
                $q->where('phone', $normalized)
                    ->orWhere('phone', $phone);
            })
            ->first();

        if ($lead) {
            $lead->update(['last_contacted_at' => now()]);
            return $lead;
        }

        return CrmLead::query()->create([
            'name' => $name ?: 'WhatsApp Contact',
            'phone' => $normalized,
            'source' => CrmLeadSourceEnum::WHATSAPP,
            'stage' => CrmLeadStageEnum::NUEVO,
            'meta_lead_id' => 'wa_' . $normalized,
            'meta_platform' => 'whatsapp',
            'assigned_agent_id' => setting('crm_meta_default_agent_id') ?: null,
            'last_contacted_at' => now(),
        ]);
    }

    protected function getConversationContext(string $phone): array
    {
        return WhatsAppConversation::query()
            ->where('phone', $phone)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($c) => [
                'direction' => $c->direction,
                'message' => $c->message,
            ])
            ->toArray();
    }
}

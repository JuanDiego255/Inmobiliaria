<?php

namespace Botble\RealEstate\Services;

use App\Models\Tenant;
use Botble\RealEstate\Enums\CrmActivityTypeEnum;
use Botble\RealEstate\Enums\CrmLeadSourceEnum;
use Botble\RealEstate\Enums\CrmLeadStageEnum;
use Botble\RealEstate\Models\CrmActivity;
use Botble\RealEstate\Models\CrmLead;
use Botble\RealEstate\Models\WhatsAppConversation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppBotService
{
    protected PropertySearchService $searchService;

    protected BotFlowService $flowService;

    protected TenantMailService $mailService;

    protected ?string $currentTenantId = null;

    protected ?string $currentTenantName = null;

    public function __construct(PropertySearchService $searchService, BotFlowService $flowService, TenantMailService $mailService)
    {
        $this->searchService = $searchService;
        $this->flowService = $flowService;
        $this->mailService = $mailService;
    }

    public function processIncomingMessage(string $from, string $name, string $message, array $metadata = []): void
    {
        if (! setting('crm_whatsapp_bot_enabled')) {
            return;
        }

        $activeTenantId = $this->getActiveTenantId($from);
        $flowMetadata = null;

        if (! $activeTenantId) {
            $result = $this->handleTenantSelection($from, $name, $message);
            $reply = $result['reply'];
            $tenantId = $result['tenant_id'];
        } elseif ($this->wantsToChangeTenant($message)) {
            $result = $this->handleTenantSelection($from, $name, $message, true);
            $reply = $result['reply'];
            $tenantId = $result['tenant_id'];
        } else {
            $tenantId = $activeTenantId;
            $flowResult = $this->handleWithFlow($from, $name, $message, $activeTenantId);

            if ($flowResult !== null) {
                $reply = $flowResult['reply'];
                $flowMetadata = $flowResult['metadata'] ?? null;

                if ($flowResult['completed'] ?? false) {
                    $this->onFlowCompleted($from, $name, $activeTenantId, $flowResult['metadata'] ?? []);
                }
            } else {
                $reply = $this->handlePropertySearch($from, $name, $message, $activeTenantId);
            }
        }

        WhatsAppConversation::query()->create([
            'phone' => $from,
            'tenant_id' => $tenantId,
            'direction' => 'inbound',
            'message' => mb_substr($message, 0, 5000),
            'metadata' => $flowMetadata ?? $metadata,
        ]);

        WhatsAppConversation::query()->create([
            'phone' => $from,
            'tenant_id' => $tenantId,
            'direction' => 'outbound',
            'message' => $reply,
            'metadata' => $flowMetadata,
        ]);

        $this->sendWhatsAppReply($from, $reply);
    }

    protected function getActiveTenantId(string $phone): ?string
    {
        $latest = WhatsAppConversation::query()
            ->where('phone', $phone)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $latest?->tenant_id;
    }

    protected function listAvailableTenants(): Collection
    {
        return Tenant::on('mysql')->orderBy('name')->get();
    }

    protected function matchTenantFromMessage(string $message, Collection $tenants): ?Tenant
    {
        $message = trim($message);
        $messageLower = mb_strtolower($message);

        if (is_numeric($message)) {
            $index = (int) $message - 1;

            return $tenants->values()->get($index);
        }

        if (preg_match('/\b(\d+)\b/', $message, $matches)) {
            $index = (int) $matches[1] - 1;
            $tenant = $tenants->values()->get($index);
            if ($tenant) {
                return $tenant;
            }
        }

        foreach ($tenants as $tenant) {
            $tenantNameLower = mb_strtolower($tenant->name);
            if ($tenantNameLower === $messageLower
                || str_contains($tenantNameLower, $messageLower)
                || str_contains($messageLower, $tenantNameLower)) {
                return $tenant;
            }
        }

        return null;
    }

    protected function wantsToChangeTenant(string $message): bool
    {
        $keywords = ['cambiar', 'otra empresa', 'otro', 'volver', 'menu', 'menú', 'inicio', 'reiniciar', 'salir'];
        $messageLower = mb_strtolower(trim($message));

        foreach ($keywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    protected function handleTenantSelection(string $from, string $name, string $message, bool $isReset = false): array
    {
        $tenants = $this->listAvailableTenants();

        if ($tenants->isEmpty()) {
            return [
                'reply' => 'Lo sentimos, no hay empresas inmobiliarias disponibles en este momento.',
                'tenant_id' => null,
            ];
        }

        if ($tenants->count() === 1) {
            $tenant = $tenants->first();
            $this->createLeadInTenant($tenant->id, $from, $name);

            return [
                'reply' => "¡Hola! 👋 Te conecté con *{$tenant->name}*.\n\n"
                    . "¿Qué tipo de propiedad estás buscando? Podés decirme:\n"
                    . "- Ubicación (ej: Escazú, Santa Ana)\n"
                    . "- Tipo (casa, apartamento, terreno)\n"
                    . "- Presupuesto\n"
                    . "- Habitaciones",
                'tenant_id' => $tenant->id,
            ];
        }

        $matched = $this->matchTenantFromMessage($message, $tenants);

        if ($matched) {
            $this->createLeadInTenant($matched->id, $from, $name);

            return [
                'reply' => "Perfecto, te conecté con *{$matched->name}* 🏠\n\n"
                    . "¿Qué tipo de propiedad estás buscando? Podés decirme:\n"
                    . "- Ubicación (ej: Escazú, Santa Ana)\n"
                    . "- Tipo (casa, apartamento, terreno)\n"
                    . "- Presupuesto\n"
                    . "- Habitaciones\n\n"
                    . "_Escribí *cambiar* para elegir otra empresa._",
                'tenant_id' => $matched->id,
            ];
        }

        $greeting = $isReset ? "Sin problema.\n\n" : "¡Hola! 👋 Un gusto atenderte.\n\n";
        $list = "¿Con cuál empresa inmobiliaria deseas consultar?\n\n";

        foreach ($tenants->values() as $i => $tenant) {
            $num = $i + 1;
            $list .= "*{$num}.* {$tenant->name}\n";
        }

        $list .= "\n_Respondé con el número o nombre de la empresa._";

        return [
            'reply' => $greeting . $list,
            'tenant_id' => null,
        ];
    }

    protected function handlePropertySearch(string $from, string $name, string $message, string $tenantId): string
    {
        $tenant = Tenant::on('mysql')->find($tenantId);
        if (! $tenant) {
            $result = $this->handleTenantSelection($from, $name, $message, true);

            return "La empresa seleccionada ya no está disponible.\n\n" . $result['reply'];
        }

        $this->currentTenantId = $tenantId;
        $this->currentTenantName = $tenant->name;

        $history = $this->getConversationContext($from, $tenantId);
        $reply = $this->callLLM($message, $history);

        try {
            $this->withTenantDb($tenantId, function () use ($from, $name, $message) {
                $lead = $this->getOrCreateLead($from, $name);
                CrmActivity::query()->create([
                    'lead_id' => $lead->id,
                    'type' => CrmActivityTypeEnum::META_AUTO,
                    'description' => 'Bot WhatsApp respondió a: "' . mb_substr($message, 0, 150) . '"',
                    'completed_at' => now(),
                ]);
            });
        } catch (\Exception $e) {
            Log::warning('WhatsAppBot: Could not create lead/activity in tenant DB', [
                'tenant' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }

        return $reply;
    }

    protected function handleWithFlow(string $from, string $name, string $message, string $tenantId): ?array
    {
        $flow = $this->flowService->getActiveFlow($tenantId);

        if (! $flow) {
            return null;
        }

        if ($this->flowService->wantsToRestart($message)) {
            $result = $this->flowService->processMessage($message, $flow, null);

            return $result;
        }

        $currentState = $this->flowService->getFlowState($from, $tenantId);
        $result = $this->flowService->processMessage($message, $flow, $currentState);

        return $result;
    }

    protected function onFlowCompleted(string $phone, string $clientName, string $tenantId, array $metadata): void
    {
        try {
            $this->withTenantDb($tenantId, function () use ($phone, $clientName, $metadata) {
                $lead = $this->getOrCreateLead($phone, $clientName);

                $activityDescription = $this->flowService->formatCollectedDataForActivity($metadata);

                CrmActivity::query()->create([
                    'lead_id' => $lead->id,
                    'type' => CrmActivityTypeEnum::META_AUTO,
                    'description' => "📋 Bot WhatsApp — Flujo completado\n\n" . $activityDescription,
                    'completed_at' => now(),
                ]);
            });
        } catch (\Exception $e) {
            Log::warning('WhatsAppBot: Could not create activity for completed flow', [
                'tenant' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }

        $leadData = [
            'client_name' => $clientName,
            'client_phone' => $phone,
            'intent' => $metadata['intent'] ?? 'Consulta',
            'option_label' => $metadata['selected_label'] ?? '',
            'collected_data' => $metadata['collected_data'] ?? [],
        ];

        $this->mailService->sendLeadNotification($tenantId, $leadData);

        $this->sendWhatsAppNotificationToAgent($tenantId, $leadData);
    }

    protected function sendWhatsAppNotificationToAgent(string $tenantId, array $data): void
    {
        $config = \Botble\RealEstate\Models\TenantMailConfig::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (! $config || empty($config->notification_whatsapp)) {
            return;
        }

        $agentPhone = preg_replace('/[^0-9]/', '', $config->notification_whatsapp);

        if (strlen($agentPhone) < 8) {
            return;
        }

        $templateName = $config->whatsapp_template_name ?: null;

        try {
            if ($templateName) {
                $sent = $this->sendWhatsAppTemplate($agentPhone, $templateName, $data);

                if (! $sent) {
                    $this->sendWhatsAppReply($agentPhone, $this->buildAgentNotificationMessage($data));
                }
            } else {
                $this->sendWhatsAppReply($agentPhone, $this->buildAgentNotificationMessage($data));
            }

            Log::info('WhatsAppBot: Agent notification sent via WhatsApp', [
                'tenant' => $tenantId,
                'agent_phone' => $agentPhone,
                'template' => $templateName,
            ]);
        } catch (\Exception $e) {
            Log::warning('WhatsAppBot: Failed to send WhatsApp notification to agent', [
                'tenant' => $tenantId,
                'agent_phone' => $agentPhone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendWhatsAppTemplate(string $to, string $templateName, array $data): bool
    {
        $phoneNumberId = setting('crm_meta_whatsapp_phone_id');
        $token = setting('crm_meta_page_access_token');

        if (! $phoneNumberId || ! $token) {
            return false;
        }

        $clientName = $data['client_name'] ?? 'Sin nombre';
        $clientPhone = $data['client_phone'] ?? '';
        $intent = $data['option_label'] ?? $data['intent'] ?? 'Consulta';

        $collectedText = '';
        foreach ($data['collected_data'] ?? [] as $item) {
            $question = $item['question'] ?? '';
            $answer = $item['answer'] ?? '';
            $collectedText .= "• {$question}: {$answer}\n";
        }

        $collectedText = $collectedText ?: 'Sin datos adicionales';
        $dateStr = now()->format('d/m/Y H:i');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => 'es'],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $clientName],
                            ['type' => 'text', 'text' => $clientPhone],
                            ['type' => 'text', 'text' => $intent],
                            ['type' => 'text', 'text' => mb_substr($collectedText, 0, 1024)],
                            ['type' => 'text', 'text' => $dateStr],
                        ],
                    ],
                ],
            ],
        ]);

        if (! $response->successful()) {
            Log::warning('WhatsAppBot: Template send failed, will try free-form', [
                'to' => $to,
                'template' => $templateName,
                'status' => $response->status(),
                'error' => $response->json('error.message', $response->body()),
            ]);

            return false;
        }

        return true;
    }

    protected function buildAgentNotificationMessage(array $data): string
    {
        $clientName = $data['client_name'] ?? 'Sin nombre';
        $clientPhone = $data['client_phone'] ?? '';
        $intent = $data['option_label'] ?? $data['intent'] ?? 'Consulta';

        $msg = "🔔 *Nuevo Lead desde WhatsApp*\n\n";
        $msg .= "👤 *Cliente:* {$clientName}\n";
        $msg .= "📱 *Teléfono:* {$clientPhone}\n";
        $msg .= "📋 *Trámite:* {$intent}\n";

        $collected = $data['collected_data'] ?? [];

        if (! empty($collected)) {
            $msg .= "\n📝 *Información recopilada:*\n";

            foreach ($collected as $field => $item) {
                $question = $item['question'] ?? $field;
                $answer = $item['answer'] ?? '';
                $msg .= "• {$question}: _{$answer}_\n";
            }
        }

        $msg .= "\n📅 " . now()->format('d/m/Y H:i');
        $msg .= "\n\n_Enviado automáticamente por el Bot._";

        return $msg;
    }

    protected function createLeadInTenant(string $tenantId, string $phone, string $name): void
    {
        try {
            $this->withTenantDb($tenantId, function () use ($phone, $name) {
                $this->getOrCreateLead($phone, $name);
            });
        } catch (\Exception $e) {
            Log::warning('WhatsAppBot: Could not create lead in tenant DB', [
                'tenant' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function resolveTenantDbName(string $tenantId): string
    {
        $tenant = Tenant::on('mysql')->find($tenantId);

        if ($tenant && $tenant->tenancy_db_name) {
            return $tenant->tenancy_db_name;
        }

        return config('tenancy.database.prefix', 'safewors_')
            . $tenantId
            . config('tenancy.database.suffix', '');
    }

    protected function withTenantDb(string $tenantId, callable $callback): mixed
    {
        $centralDb = config('database.connections.mysql.database');
        $tenantDb = $this->resolveTenantDbName($tenantId);

        config(['database.connections.mysql.database' => $tenantDb]);
        DB::purge('mysql');
        DB::reconnect('mysql');

        try {
            return $callback();
        } finally {
            config(['database.connections.mysql.database' => $centralDb]);
            DB::purge('mysql');
            DB::reconnect('mysql');
        }
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

    protected function processLLMResponse(array $data, array $messages, string $apiKey, string $model, string $systemPrompt, array $tools, int $iteration = 0): string
    {
        $maxIterations = 3;
        $textParts = [];
        $toolUses = [];

        foreach ($data['content'] ?? [] as $block) {
            if ($block['type'] === 'text') {
                $textParts[] = $block['text'];
            } elseif ($block['type'] === 'tool_use') {
                $toolUses[] = $block;
            }
        }

        if (empty($toolUses) || $iteration >= $maxIterations) {
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

        return $this->processLLMResponse($followUp->json(), $messages, $apiKey, $model, $systemPrompt, $tools, $iteration + 1);
    }

    protected function processOpenAIResponse(array $data, array $messages, string $apiKey, string $model, array $tools, int $iteration = 0): string
    {
        $maxIterations = 3;
        $choice = $data['choices'][0] ?? [];
        $assistantMessage = $choice['message'] ?? [];

        $toolCalls = $assistantMessage['tool_calls'] ?? [];

        if (empty($toolCalls) || $iteration >= $maxIterations) {
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

        return $this->processOpenAIResponse($followUp->json(), $messages, $apiKey, $model, $tools, $iteration + 1);
    }

    protected function handleToolCall(string $tool, array $params): string
    {
        if (! $this->currentTenantId) {
            return json_encode(['error' => 'No hay empresa seleccionada.']);
        }

        Log::info('WhatsAppBot: Tool call', [
            'tool' => $tool,
            'params' => $params,
            'tenant' => $this->currentTenantId,
            'db' => $this->resolveTenantDbName($this->currentTenantId),
        ]);

        return $this->withTenantDb($this->currentTenantId, function () use ($tool, $params) {
            $result = match ($tool) {
                'search_properties' => $this->toolSearchProperties($params),
                'get_property_detail' => $this->toolGetPropertyDetail($params),
                default => 'Herramienta no reconocida.',
            };

            Log::info('WhatsAppBot: Tool result', [
                'db_connected' => DB::connection()->getDatabaseName(),
                'result_preview' => mb_substr($result, 0, 500),
            ]);

            return $result;
        });
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
                'type' => $property->type instanceof \Botble\Base\Supports\Enum ? $property->type->getValue() : (string) $property->type,
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
            'type' => $property->type instanceof \Botble\Base\Supports\Enum ? $property->type->getValue() : (string) $property->type,
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
        $company = $this->currentTenantName ?: 'la inmobiliaria';

        if ($custom) {
            return $custom . "\n\nEstás atendiendo en nombre de: {$company}. "
                . 'Si es la primera interacción, mencioná que pueden escribir "cambiar" para elegir otra empresa.';
        }

        return <<<PROMPT
Sos un asistente inmobiliario profesional y amigable de {$company}. Tu trabajo es ayudar a clientes a encontrar propiedades según sus necesidades.

Reglas:
- Respondé siempre en español, de forma concisa y profesional.
- Usá la herramienta search_properties para buscar propiedades cuando el cliente pregunte por opciones.
- Usá get_property_detail cuando el cliente pida más información de una propiedad específica.
- No inventés propiedades. Solo mostrá las que devuelve la herramienta de búsqueda.
- Formateá las respuestas para WhatsApp: usá *negrita* para nombres y precios, emojis moderados.
- Sé breve — máximo 3-4 propiedades por mensaje para no saturar.
- Si es la primera respuesta, mencioná brevemente que pueden escribir *cambiar* para consultar con otra empresa inmobiliaria.
- Si el cliente quiere agendar una visita o hablar con un agente, indicale que un asesor de {$company} lo contactará pronto.

Estrategia de búsqueda (MUY IMPORTANTE):
- Empezá siempre con pocos filtros. Usá "keyword" para texto libre en vez de combinar location + category.
- NUNCA repitas una búsqueda exacta que ya devolvió 0 resultados. Si no hay match, quitá filtros y ampliá.
- Si buscás por category y no hay resultados, intentá sin category (solo con location o keyword).
- Si no hay resultados con ningún filtro, hacé una búsqueda vacía (sin parámetros) para ver qué propiedades hay disponibles, y presentá las opciones.
- Llamá search_properties una sola vez por intento — no repitas la misma búsqueda.
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
                        'keyword' => ['type' => 'string', 'description' => 'Texto libre de búsqueda. Busca en nombre de propiedad, ubicación, descripción, provincia, ciudad, proyecto y categorías. Usá este campo como primera opción.'],
                        'type' => ['type' => 'string', 'enum' => ['sale', 'rent'], 'description' => 'Tipo: sale (venta) o rent (alquiler)'],
                        'min_price' => ['type' => 'number', 'description' => 'Precio mínimo'],
                        'max_price' => ['type' => 'number', 'description' => 'Precio máximo'],
                        'bedrooms' => ['type' => 'integer', 'description' => 'Número de habitaciones'],
                        'bathrooms' => ['type' => 'integer', 'description' => 'Número de baños'],
                        'location' => ['type' => 'string', 'description' => 'Filtro específico por provincia o ciudad (busca en provincia y ciudad, no en categoría)'],
                        'category' => ['type' => 'string', 'description' => 'Filtro por categoría exacta como está en el sistema (puede variar, usá keyword si no estás seguro del nombre exacto)'],
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
                            'keyword' => ['type' => 'string', 'description' => 'Texto libre. Busca en nombre, ubicación, descripción, provincia, ciudad, proyecto y categorías. Usá este como primera opción.'],
                            'type' => ['type' => 'string', 'enum' => ['sale', 'rent'], 'description' => 'sale (venta) o rent (alquiler)'],
                            'min_price' => ['type' => 'number', 'description' => 'Precio mínimo'],
                            'max_price' => ['type' => 'number', 'description' => 'Precio máximo'],
                            'bedrooms' => ['type' => 'integer', 'description' => 'Número de habitaciones'],
                            'bathrooms' => ['type' => 'integer', 'description' => 'Número de baños'],
                            'location' => ['type' => 'string', 'description' => 'Filtro específico por provincia o ciudad'],
                            'category' => ['type' => 'string', 'description' => 'Filtro por categoría exacta del sistema (usá keyword si no sabés el nombre exacto)'],
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

    protected function getConversationContext(string $phone, ?string $tenantId = null): array
    {
        $query = WhatsAppConversation::query()
            ->where('phone', $phone);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->orderBy('created_at', 'desc')
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

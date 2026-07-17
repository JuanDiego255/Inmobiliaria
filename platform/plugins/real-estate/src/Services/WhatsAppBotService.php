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
use Botble\RealEstate\Models\TenantFeature;
use Illuminate\Support\Facades\Log;

class WhatsAppBotService
{
    protected PropertySearchService $searchService;

    protected BotFlowService $flowService;

    protected TenantMailService $mailService;

    protected VehicleSearchService $vehicleSearchService;

    protected ?string $currentTenantId = null;

    protected ?string $currentTenantName = null;

    protected array $lastSearchResults = [];

    protected bool $interactiveSent = false;

    public function __construct(PropertySearchService $searchService, BotFlowService $flowService, TenantMailService $mailService, VehicleSearchService $vehicleSearchService)
    {
        $this->searchService = $searchService;
        $this->flowService = $flowService;
        $this->mailService = $mailService;
        $this->vehicleSearchService = $vehicleSearchService;
    }

    protected function isVehicleMode(): bool
    {
        return (bool) setting('crm_whatsapp_bot_vehicle_mode');
    }

    public function processIncomingMessage(string $from, string $name, string $message, array $metadata = []): void
    {
        if (! setting('crm_whatsapp_bot_enabled')) {
            return;
        }

        $this->interactiveSent = false;
        $this->lastSearchResults = [];
        $activeTenantId = $this->getActiveTenantId($from);
        $flowMetadata = null;

        if ($this->isVehicleMode()) {
            $tenantId = $activeTenantId ?: $this->autoAssignVehicleTenant($from, $name);

            $flowResult = $this->handleWithFlow($from, $name, $message, $tenantId);

            if ($flowResult !== null) {
                if ($flowResult['trigger_ai'] ?? false) {
                    $flowMetadata = $flowResult['metadata'] ?? null;
                    $contextMessage = $this->buildAiContextFromFlow($message, $flowMetadata);
                    $reply = $this->handlePropertySearch($from, $name, $contextMessage, $tenantId);
                    $this->onFlowCompleted($from, $name, $tenantId, $flowMetadata ?? []);
                } else {
                    $reply = $flowResult['reply'];
                    $flowMetadata = $flowResult['metadata'] ?? null;

                    if ($flowResult['completed'] ?? false) {
                        $this->onFlowCompleted($from, $name, $tenantId, $flowMetadata ?? []);
                    }
                }
            } else {
                $reply = $this->handlePropertySearch($from, $name, $message, $tenantId);
            }
        } elseif (! $activeTenantId) {
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
                if ($flowResult['trigger_ai'] ?? false) {
                    $flowMetadata = $flowResult['metadata'] ?? null;
                    $contextMessage = $this->buildAiContextFromFlow($message, $flowMetadata);
                    $reply = $this->handlePropertySearch($from, $name, $contextMessage, $activeTenantId);
                    $this->onFlowCompleted($from, $name, $activeTenantId, $flowMetadata ?? []);
                } else {
                    $reply = $flowResult['reply'];
                    $flowMetadata = $flowResult['metadata'] ?? null;

                    if ($flowResult['completed'] ?? false) {
                        $this->onFlowCompleted($from, $name, $activeTenantId, $flowResult['metadata'] ?? []);
                    }
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

        if (! $this->interactiveSent) {
            $this->sendWhatsAppReply($from, $reply);
        }

        if ($tenantId && $this->tenantHasFeature($tenantId, 'photos') && ! empty($this->lastSearchResults)) {
            $this->sendPropertyPhotos($from, $this->lastSearchResults);
        }
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

    protected function autoAssignVehicleTenant(string $from, string $name): string
    {
        $tenants = $this->listAvailableTenants();
        $tenant = $tenants->first();

        if (! $tenant) {
            return '';
        }

        $this->createLeadInTenant($tenant->id, $from, $name);

        return $tenant->id;
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

            if ($this->isVehicleMode()) {
                $dealerName = setting('crm_whatsapp_bot_vehicle_dealer_name') ?: $tenant->name;
                return [
                    'reply' => "¡Hola! 👋 Bienvenido a *{$dealerName}*.\n\n"
                        . "¿Qué vehículo estás buscando? Podés decirme:\n"
                        . "- Marca y modelo (ej: Hyundai Tucson)\n"
                        . "- Año (ej: 2020 en adelante)\n"
                        . "- Presupuesto\n"
                        . "- Transmisión (automática o manual)",
                    'tenant_id' => $tenant->id,
                ];
            }

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

            if ($this->isVehicleMode()) {
                $dealerName = setting('crm_whatsapp_bot_vehicle_dealer_name') ?: $matched->name;
                return [
                    'reply' => "Perfecto, te conecté con *{$dealerName}* 🚗\n\n"
                        . "¿Qué vehículo estás buscando? Podés decirme:\n"
                        . "- Marca y modelo (ej: Toyota Corolla)\n"
                        . "- Año\n"
                        . "- Presupuesto\n"
                        . "- Transmisión\n\n"
                        . "_Escribí *cambiar* para elegir otra empresa._",
                    'tenant_id' => $matched->id,
                ];
            }

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
        $list = $this->isVehicleMode()
            ? "¿Con cuál concesionario deseas consultar?\n\n"
            : "¿Con cuál empresa inmobiliaria deseas consultar?\n\n";

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

            if ($this->tenantHasFeature($tenantId, 'interactive_buttons')) {
                $sent = $this->sendFlowAsInteractive($from, $result, $flow);
                if ($sent) {
                    $this->interactiveSent = true;
                }
            }

            return $result;
        }

        $currentState = $this->flowService->getFlowState($from, $tenantId);
        $result = $this->flowService->processMessage($message, $flow, $currentState);

        if ($this->tenantHasFeature($tenantId, 'interactive_buttons') && ! ($result['completed'] ?? false)) {
            $sent = $this->sendFlowAsInteractive($from, $result, $flow);
            if ($sent) {
                $this->interactiveSent = true;
            }
        }

        return $result;
    }

    protected function buildAiContextFromFlow(string $lastMessage, ?array $metadata): string
    {
        if (! $metadata || empty($metadata['collected_data'])) {
            return $lastMessage;
        }

        $parts = [];
        foreach ($metadata['collected_data'] as $data) {
            $q = $data['question'] ?? '';
            $a = $data['answer'] ?? '';
            if ($q && $a) {
                $parts[] = "{$q}: {$a}";
            }
        }

        $context = implode('. ', $parts);

        return "Basándote en lo siguiente que recopilé del cliente: {$context}. Último mensaje: {$lastMessage}. Buscá opciones que coincidan.";
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
            'mode' => $this->isVehicleMode() ? 'vehicles' : 'realtor',
        ]);

        if ($this->isVehicleMode() && in_array($tool, ['search_vehicles', 'get_vehicle_detail'])) {
            $result = match ($tool) {
                'search_vehicles' => $this->toolSearchVehicles($params),
                'get_vehicle_detail' => $this->toolGetVehicleDetail($params),
                default => 'Herramienta no reconocida.',
            };

            Log::info('WhatsAppBot: Vehicle tool result', [
                'result_preview' => mb_substr($result, 0, 500),
            ]);

            return $result;
        }

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

        $this->lastSearchResults = [];
        foreach ($properties as $prop) {
            $image = $prop->image;
            if ($image) {
                $imageUrl = \Botble\Media\Facades\RvMedia::getImageUrl($image, 'medium');
                if ($imageUrl && ! str_starts_with($imageUrl, 'data:')) {
                    if (! str_starts_with($imageUrl, 'http')) {
                        $imageUrl = url($imageUrl);
                    }
                    $this->lastSearchResults[] = [
                        'name' => $prop->name,
                        'image_url' => $imageUrl,
                    ];
                }
            }
        }

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

    protected function toolSearchVehicles(array $params): string
    {
        $vehicles = $this->vehicleSearchService->searchVehicles($params);

        $this->lastSearchResults = [];
        foreach ($vehicles as $vehicle) {
            if (! empty($vehicle['main_image'])) {
                $this->lastSearchResults[] = [
                    'name' => $vehicle['title'] ?? $vehicle['name'] ?? '',
                    'image_url' => $vehicle['main_image'],
                ];
            }
        }

        if (empty($vehicles)) {
            return json_encode([
                'results' => 0,
                'message' => 'No se encontraron vehículos con esos criterios.',
            ]);
        }

        $results = [];
        foreach ($vehicles as $v) {
            $results[] = [
                'id' => $v['id'],
                'name' => $v['name'] ?? '',
                'title' => $v['title'] ?? '',
                'brand' => $v['brand'] ?? '',
                'model' => $v['model'] ?? '',
                'year' => $v['year'] ?? '',
                'price' => $v['formatted_price'] ?? '',
                'color' => $v['color'] ?? '',
                'mileage_km' => $v['mileage_km'] ?? '',
                'transmission' => $v['transmission'] ?? '',
                'fuel_type' => $v['fuel_type'] ?? '',
                'condition' => $v['condition'] ?? '',
                'location' => $v['location'] ?? '',
                'doors' => $v['doors'] ?? '',
                'passengers' => $v['passengers'] ?? '',
                'has_tour' => $v['has_virtual_tour'] ?? false,
            ];
        }

        return json_encode(['results' => count($results), 'vehicles' => $results]);
    }

    protected function toolGetVehicleDetail(array $params): string
    {
        $vehicleId = (int) ($params['vehicle_id'] ?? 0);
        if (! $vehicleId) {
            return json_encode(['error' => 'Se requiere vehicle_id.']);
        }

        $vehicle = $this->vehicleSearchService->getVehicleDetail($vehicleId);
        if (! $vehicle) {
            return json_encode(['error' => 'Vehículo no encontrado.']);
        }

        return json_encode([
            'id' => $vehicle['id'],
            'name' => $vehicle['name'] ?? '',
            'title' => $vehicle['title'] ?? '',
            'brand' => $vehicle['brand'] ?? '',
            'model' => $vehicle['model'] ?? '',
            'year' => $vehicle['year'] ?? '',
            'price' => $vehicle['formatted_price'] ?? '',
            'color' => $vehicle['color'] ?? '',
            'mileage_km' => $vehicle['mileage_km'] ?? '',
            'transmission' => $vehicle['transmission'] ?? '',
            'fuel_type' => $vehicle['fuel_type'] ?? '',
            'condition' => $vehicle['condition'] ?? '',
            'location' => $vehicle['location'] ?? '',
            'doors' => $vehicle['doors'] ?? '',
            'passengers' => $vehicle['passengers'] ?? '',
            'engine_cc' => $vehicle['engine_cc'] ?? '',
            'drivetrain' => $vehicle['drivetrain'] ?? '',
            'description' => $vehicle['description'] ? mb_substr(strip_tags($vehicle['description']), 0, 500) : null,
            'has_tour' => $vehicle['has_virtual_tour'] ?? false,
            'tour_url' => $vehicle['tour_url'] ?? null,
            'images' => array_map(fn ($img) => $img['url'] ?? '', $vehicle['images'] ?? []),
        ]);
    }

    protected function buildSystemPrompt(): string
    {
        if ($this->isVehicleMode()) {
            return $this->buildVehicleSystemPrompt();
        }

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

    protected function buildVehicleSystemPrompt(): string
    {
        $custom = setting('crm_whatsapp_bot_vehicle_system_prompt');
        $dealerName = setting('crm_whatsapp_bot_vehicle_dealer_name') ?: 'Autos Grecia';

        if ($custom) {
            return $custom . "\n\nEstás atendiendo en nombre de: *{$dealerName}*. "
                . 'Si es la primera interacción, mencioná que pueden escribir "cambiar" para elegir otra empresa.';
        }

        return <<<PROMPT
Sos un asesor de ventas de vehículos profesional y amigable de *{$dealerName}*. Tu trabajo es ayudar a clientes a encontrar el vehículo ideal según sus necesidades.

Reglas:
- Respondé siempre en español, de forma concisa y profesional.
- Usá la herramienta search_vehicles para buscar vehículos cuando el cliente pregunte por opciones.
- Usá get_vehicle_detail cuando el cliente pida más información de un vehículo específico.
- No inventés vehículos. Solo mostrá los que devuelve la herramienta de búsqueda.
- Formateá las respuestas para WhatsApp: usá *negrita* para marcas, modelos y precios, emojis moderados.
- Sé breve — máximo 3-4 vehículos por mensaje para no saturar.
- Si es la primera respuesta, mencioná brevemente que pueden escribir *cambiar* para consultar con otra empresa.
- Si el cliente quiere agendar una prueba de manejo o hablar con un asesor, indicale que un vendedor de {$dealerName} lo contactará pronto.
- Si el vehículo tiene tour virtual (has_tour = true), mencionalo como ventaja: "Podés ver este vehículo en 360° desde tu celular".

Estrategia de búsqueda (MUY IMPORTANTE):
- Cuando el cliente mencione una marca y modelo (ej: "Hyundai Tucson"), usá los campos brand y model separados.
- Si mencionan el año, usá year o year_min.
- Empezá con pocos filtros. Si no hay resultados, quitá filtros progresivamente.
- Usá "keyword" (campo q) para búsquedas de texto libre cuando no estés seguro de los valores exactos.
- NUNCA repitas una búsqueda exacta que ya devolvió 0 resultados.
- Llamá search_vehicles una sola vez por intento.
PROMPT;
    }

    protected function getToolDefinitions(): array
    {
        if ($this->isVehicleMode()) {
            return $this->getVehicleToolDefinitions();
        }

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
        if ($this->isVehicleMode()) {
            return $this->getOpenAIVehicleToolDefinitions();
        }

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

    public function sendWhatsAppImage(string $to, string $imageUrl, string $caption = ''): void
    {
        $phoneNumberId = setting('crm_meta_whatsapp_phone_id');
        $token = setting('crm_meta_page_access_token');

        if (! $phoneNumberId || ! $token) {
            return;
        }

        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'image',
                'image' => [
                    'link' => $imageUrl,
                ],
            ];

            if ($caption) {
                $payload['image']['caption'] = mb_substr($caption, 0, 1024);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", $payload);

            if (! $response->successful()) {
                Log::warning('WhatsAppBot: Failed to send image', [
                    'to' => $to,
                    'status' => $response->status(),
                    'error' => $response->json('error.message', ''),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('WhatsAppBot: Exception sending image', ['error' => $e->getMessage()]);
        }
    }

    public function sendWhatsAppInteractive(string $to, array $interactive): void
    {
        $phoneNumberId = setting('crm_meta_whatsapp_phone_id');
        $token = setting('crm_meta_page_access_token');

        if (! $phoneNumberId || ! $token) {
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'interactive',
                'interactive' => $interactive,
            ]);

            if (! $response->successful()) {
                Log::warning('WhatsAppBot: Failed to send interactive message', [
                    'to' => $to,
                    'status' => $response->status(),
                    'error' => $response->json('error.message', ''),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('WhatsAppBot: Exception sending interactive', ['error' => $e->getMessage()]);
        }
    }

    public function sendWhatsAppDocument(string $to, string $documentUrl, string $filename, string $caption = ''): void
    {
        $phoneNumberId = setting('crm_meta_whatsapp_phone_id');
        $token = setting('crm_meta_page_access_token');

        if (! $phoneNumberId || ! $token) {
            return;
        }

        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'document',
                'document' => [
                    'link' => $documentUrl,
                    'filename' => $filename,
                ],
            ];

            if ($caption) {
                $payload['document']['caption'] = mb_substr($caption, 0, 1024);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", $payload);

            if (! $response->successful()) {
                Log::warning('WhatsAppBot: Failed to send document', [
                    'to' => $to,
                    'status' => $response->status(),
                    'error' => $response->json('error.message', ''),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('WhatsAppBot: Exception sending document', ['error' => $e->getMessage()]);
        }
    }

    protected function sendPropertyPhotos(string $to, array $results): void
    {
        $maxPhotos = 3;
        $sent = 0;

        foreach ($results as $result) {
            if ($sent >= $maxPhotos) {
                break;
            }

            if (! empty($result['image_url'])) {
                $this->sendWhatsAppImage($to, $result['image_url'], $result['name'] ?? '');
                $sent++;
            }
        }
    }

    protected function sendFlowAsInteractive(string $from, array $result, $flow): bool
    {
        $state = $result['metadata'] ?? [];
        $flowState = $state['flow_state'] ?? '';

        if ($flowState === 'awaiting_option') {
            $options = $flow->flow_config['options'] ?? [];
            if (count($options) >= 1 && count($options) <= 10) {
                $rows = [];
                foreach ($options as $option) {
                    $rows[] = [
                        'id' => 'opt_' . ($option['key'] ?? ''),
                        'title' => mb_substr($option['label'] ?? '', 0, 24),
                        'description' => mb_substr($option['intent'] ?? '', 0, 72),
                    ];
                }

                $this->sendWhatsAppInteractive($from, [
                    'type' => 'list',
                    'header' => [
                        'type' => 'text',
                        'text' => mb_substr($flow->name ?? 'Menú', 0, 60),
                    ],
                    'body' => [
                        'text' => $flow->greeting_message ?: '¿Qué trámite desea realizar?',
                    ],
                    'action' => [
                        'button' => 'Ver opciones',
                        'sections' => [
                            [
                                'title' => 'Opciones',
                                'rows' => $rows,
                            ],
                        ],
                    ],
                ]);

                return true;
            }
        } elseif ($flowState === 'collecting') {
            $selectedKey = $state['selected_option'] ?? '';
            $currentStepIndex = $state['current_step'] ?? 0;
            $options = $flow->flow_config['options'] ?? [];
            $selectedOption = null;
            foreach ($options as $option) {
                if ($option['key'] === $selectedKey) {
                    $selectedOption = $option;
                    break;
                }
            }

            if ($selectedOption) {
                $steps = $selectedOption['steps'] ?? [];
                $currentStep = $steps[$currentStepIndex] ?? null;

                if ($currentStep && ($currentStep['type'] ?? '') === 'choice' && ! empty($currentStep['choices'])) {
                    $choices = $currentStep['choices'];
                    if (count($choices) <= 3) {
                        $buttons = [];
                        foreach ($choices as $i => $choice) {
                            $buttons[] = [
                                'type' => 'reply',
                                'reply' => [
                                    'id' => 'choice_' . ($i + 1),
                                    'title' => mb_substr($choice, 0, 20),
                                ],
                            ];
                        }

                        $this->sendWhatsAppInteractive($from, [
                            'type' => 'button',
                            'body' => ['text' => $currentStep['question']],
                            'action' => ['buttons' => $buttons],
                        ]);

                        return true;
                    } elseif (count($choices) <= 10) {
                        $rows = [];
                        foreach ($choices as $i => $choice) {
                            $rows[] = [
                                'id' => 'choice_' . ($i + 1),
                                'title' => mb_substr($choice, 0, 24),
                            ];
                        }

                        $this->sendWhatsAppInteractive($from, [
                            'type' => 'list',
                            'body' => ['text' => $currentStep['question']],
                            'action' => [
                                'button' => 'Ver opciones',
                                'sections' => [['title' => 'Opciones', 'rows' => $rows]],
                            ],
                        ]);

                        return true;
                    }
                } elseif ($currentStep && ($currentStep['type'] ?? '') === 'yes_no') {
                    $this->sendWhatsAppInteractive($from, [
                        'type' => 'button',
                        'body' => ['text' => $currentStep['question']],
                        'action' => [
                            'buttons' => [
                                ['type' => 'reply', 'reply' => ['id' => 'btn_si', 'title' => 'Sí']],
                                ['type' => 'reply', 'reply' => ['id' => 'btn_no', 'title' => 'No']],
                            ],
                        ],
                    ]);

                    return true;
                }
            }
        }

        return false;
    }

    protected function tenantHasFeature(string $tenantId, string $feature): bool
    {
        $features = TenantFeature::for($tenantId);
        return $features->hasFeature($feature);
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

    protected function getVehicleToolDefinitions(): array
    {
        return [
            [
                'name' => 'search_vehicles',
                'description' => 'Buscar vehículos disponibles en el concesionario según criterios del cliente.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'keyword' => ['type' => 'string', 'description' => 'Texto libre. Busca en marca, modelo, nombre, código y placa.'],
                        'brand' => ['type' => 'string', 'description' => 'Marca del vehículo (ej: Toyota, Hyundai, Nissan)'],
                        'model' => ['type' => 'string', 'description' => 'Modelo del vehículo (ej: Tucson, Corolla, Hilux)'],
                        'year' => ['type' => 'integer', 'description' => 'Año exacto del vehículo'],
                        'year_min' => ['type' => 'integer', 'description' => 'Año mínimo'],
                        'year_max' => ['type' => 'integer', 'description' => 'Año máximo'],
                        'color' => ['type' => 'string', 'description' => 'Color del vehículo'],
                        'transmission' => ['type' => 'string', 'description' => 'Tipo de transmisión (Automática, Manual)'],
                        'fuel_type' => ['type' => 'string', 'description' => 'Tipo de combustible (Gasolina, Diésel, Híbrido)'],
                        'condition' => ['type' => 'string', 'description' => 'Condición (Nuevo, Usado)'],
                        'price_min' => ['type' => 'number', 'description' => 'Precio mínimo'],
                        'price_max' => ['type' => 'number', 'description' => 'Precio máximo'],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'get_vehicle_detail',
                'description' => 'Obtener detalles completos de un vehículo específico por su ID.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'vehicle_id' => ['type' => 'integer', 'description' => 'ID del vehículo'],
                    ],
                    'required' => ['vehicle_id'],
                ],
            ],
        ];
    }

    protected function getOpenAIVehicleToolDefinitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_vehicles',
                    'description' => 'Buscar vehículos disponibles según criterios del cliente.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'keyword' => ['type' => 'string', 'description' => 'Texto libre. Busca en marca, modelo, nombre, código y placa.'],
                            'brand' => ['type' => 'string', 'description' => 'Marca (Toyota, Hyundai, Nissan, etc.)'],
                            'model' => ['type' => 'string', 'description' => 'Modelo (Tucson, Corolla, Hilux, etc.)'],
                            'year' => ['type' => 'integer', 'description' => 'Año exacto'],
                            'year_min' => ['type' => 'integer', 'description' => 'Año mínimo'],
                            'year_max' => ['type' => 'integer', 'description' => 'Año máximo'],
                            'color' => ['type' => 'string', 'description' => 'Color'],
                            'transmission' => ['type' => 'string', 'description' => 'Transmisión (Automática, Manual)'],
                            'fuel_type' => ['type' => 'string', 'description' => 'Combustible (Gasolina, Diésel, Híbrido)'],
                            'condition' => ['type' => 'string', 'description' => 'Condición (Nuevo, Usado)'],
                            'price_min' => ['type' => 'number', 'description' => 'Precio mínimo'],
                            'price_max' => ['type' => 'number', 'description' => 'Precio máximo'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_vehicle_detail',
                    'description' => 'Obtener detalles completos de un vehículo por su ID.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'vehicle_id' => ['type' => 'integer', 'description' => 'ID del vehículo'],
                        ],
                        'required' => ['vehicle_id'],
                    ],
                ],
            ],
        ];
    }
}

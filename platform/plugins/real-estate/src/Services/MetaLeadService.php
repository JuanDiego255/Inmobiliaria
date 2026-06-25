<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Enums\CrmActivityTypeEnum;
use Botble\RealEstate\Enums\CrmLeadSourceEnum;
use Botble\RealEstate\Enums\CrmLeadStageEnum;
use Botble\RealEstate\Models\CrmActivity;
use Botble\RealEstate\Models\CrmLead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Botble\RealEstate\Services\WhatsAppBotService;

class MetaLeadService
{
    public function processLeadgenEvent(array $entry): void
    {
        foreach ($entry['changes'] ?? [] as $change) {
            if (($change['field'] ?? '') !== 'leadgen') {
                continue;
            }

            $leadgenId = $change['value']['leadgen_id'] ?? null;
            if (! $leadgenId) {
                continue;
            }

            $fields = $this->fetchLeadAdData($leadgenId);
            if (! $fields) {
                continue;
            }

            $data = $this->buildLeadFromFields($fields);
            $data['source'] = CrmLeadSourceEnum::FACEBOOK_LEAD_AD;
            $data['meta_lead_id'] = (string) $leadgenId;
            $data['meta_platform'] = 'facebook';
            $data['activity_description'] = 'Lead capturado desde Facebook/Instagram Lead Ad (ID: ' . $leadgenId . ')';

            $this->createOrUpdateLead($data);
        }
    }

    public function processWhatsAppMessage(array $entry): void
    {
        foreach ($entry['changes'] ?? [] as $change) {
            if (($change['field'] ?? '') !== 'messages') {
                continue;
            }

            $value = $change['value'] ?? [];
            $contacts = $value['contacts'] ?? [];
            $messages = $value['messages'] ?? [];

            foreach ($messages as $index => $message) {
                $waId = $message['from'] ?? null;
                if (! $waId) {
                    continue;
                }

                $contactName = $contacts[$index]['profile']['name'] ?? ($contacts[0]['profile']['name'] ?? 'WhatsApp Contact');
                $messageText = $this->extractWhatsAppMessageText($message);

                if (setting('crm_whatsapp_bot_enabled') && $messageText && ($message['type'] ?? 'text') === 'text') {
                    try {
                        $botService = app(WhatsAppBotService::class);
                        $botService->processIncomingMessage($waId, $contactName, $messageText, [
                            'message_id' => $message['id'] ?? null,
                            'timestamp' => $message['timestamp'] ?? null,
                        ]);
                        continue;
                    } catch (\Exception $e) {
                        Log::error('WhatsAppBot: Failed, falling back to lead creation', ['error' => $e->getMessage()]);
                    }
                }

                $data = [
                    'name' => $contactName,
                    'phone' => $this->normalizePhone($waId),
                    'source' => CrmLeadSourceEnum::WHATSAPP,
                    'meta_lead_id' => 'wa_' . $waId,
                    'meta_platform' => 'whatsapp',
                    'notes' => $messageText ? 'Mensaje: ' . mb_substr($messageText, 0, 500) : null,
                    'activity_description' => 'Mensaje recibido vía WhatsApp de ' . $contactName . ($messageText ? ': "' . mb_substr($messageText, 0, 200) . '"' : ''),
                ];

                $this->createOrUpdateLead($data);
            }
        }
    }

    public function processMessengerEvent(array $entry): void
    {
        foreach ($entry['messaging'] ?? [] as $event) {
            $senderId = $event['sender']['id'] ?? null;
            if (! $senderId) {
                continue;
            }

            $recipientId = $event['recipient']['id'] ?? null;
            if ($senderId === $recipientId) {
                continue;
            }

            $messageText = $event['message']['text'] ?? null;
            $senderName = $this->fetchFacebookUserName($senderId);

            $data = [
                'name' => $senderName ?: 'Messenger Contact',
                'source' => CrmLeadSourceEnum::MESSENGER,
                'meta_lead_id' => 'msngr_' . $senderId,
                'meta_platform' => 'messenger',
                'notes' => $messageText ? 'Mensaje: ' . mb_substr($messageText, 0, 500) : null,
                'activity_description' => 'Mensaje recibido vía Messenger de ' . ($senderName ?: $senderId) . ($messageText ? ': "' . mb_substr($messageText, 0, 200) . '"' : ''),
            ];

            $this->createOrUpdateLead($data);
        }
    }

    public function processInstagramDMEvent(array $entry): void
    {
        foreach ($entry['messaging'] ?? [] as $event) {
            $senderId = $event['sender']['id'] ?? null;
            if (! $senderId) {
                continue;
            }

            $recipientId = $event['recipient']['id'] ?? null;
            if ($senderId === $recipientId) {
                continue;
            }

            $messageText = $event['message']['text'] ?? null;

            $data = [
                'name' => 'Instagram User ' . substr($senderId, -6),
                'source' => CrmLeadSourceEnum::INSTAGRAM_DM,
                'meta_lead_id' => 'ig_' . $senderId,
                'meta_platform' => 'instagram',
                'notes' => $messageText ? 'Mensaje: ' . mb_substr($messageText, 0, 500) : null,
                'activity_description' => 'Mensaje recibido vía Instagram DM' . ($messageText ? ': "' . mb_substr($messageText, 0, 200) . '"' : ''),
            ];

            $this->createOrUpdateLead($data);
        }
    }

    public function createOrUpdateLead(array $data): CrmLead
    {
        $activityDesc = $data['activity_description'] ?? 'Actividad Meta automática';
        unset($data['activity_description']);

        $lead = null;

        if (! empty($data['meta_lead_id'])) {
            $lead = CrmLead::query()->where('meta_lead_id', $data['meta_lead_id'])->first();
        }

        if (! $lead && ! empty($data['email'])) {
            $lead = CrmLead::query()->where('email', $data['email'])->first();
        }

        if (! $lead && ! empty($data['phone'])) {
            $normalized = $this->normalizePhone($data['phone']);
            $lead = CrmLead::query()
                ->where(function ($q) use ($normalized, $data) {
                    $q->where('phone', $normalized)
                        ->orWhere('phone', $data['phone']);
                })
                ->first();
        }

        $defaultAgentId = setting('crm_meta_default_agent_id');

        if ($lead) {
            $lead->update(array_filter([
                'last_contacted_at' => now(),
                'meta_lead_id' => $lead->meta_lead_id ?: ($data['meta_lead_id'] ?? null),
                'meta_platform' => $lead->meta_platform ?: ($data['meta_platform'] ?? null),
            ]));

            CrmActivity::query()->create([
                'lead_id' => $lead->id,
                'type' => CrmActivityTypeEnum::META_AUTO,
                'description' => $activityDesc,
                'completed_at' => now(),
            ]);
        } else {
            $lead = CrmLead::query()->create(array_filter([
                'name' => $data['name'] ?? 'Lead Meta',
                'email' => $data['email'] ?? null,
                'phone' => isset($data['phone']) ? $this->normalizePhone($data['phone']) : null,
                'stage' => CrmLeadStageEnum::NUEVO,
                'source' => $data['source'],
                'meta_lead_id' => $data['meta_lead_id'] ?? null,
                'meta_platform' => $data['meta_platform'] ?? null,
                'notes' => $data['notes'] ?? null,
                'assigned_agent_id' => $defaultAgentId ?: null,
                'last_contacted_at' => now(),
            ]));

            CrmActivity::query()->create([
                'lead_id' => $lead->id,
                'type' => CrmActivityTypeEnum::META_AUTO,
                'description' => $activityDesc,
                'completed_at' => now(),
            ]);
        }

        return $lead;
    }

    public function fetchLeadAdData(string $leadgenId): ?array
    {
        $token = setting('crm_meta_page_access_token');
        if (! $token) {
            Log::warning('MetaLeadService: No page access token configured for Lead Ad fetch.');
            return null;
        }

        try {
            $response = Http::get("https://graph.facebook.com/v21.0/{$leadgenId}", [
                'access_token' => $token,
            ]);

            if (! $response->successful()) {
                Log::error('MetaLeadService: Graph API error fetching lead ' . $leadgenId, [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MetaLeadService: Exception fetching lead ad data', [
                'leadgen_id' => $leadgenId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function buildLeadFromFields(array $leadData): array
    {
        $fields = [];
        foreach ($leadData['field_data'] ?? [] as $field) {
            $name = strtolower($field['name'] ?? '');
            $value = $field['values'][0] ?? null;
            if ($name && $value) {
                $fields[$name] = $value;
            }
        }

        return [
            'name' => $fields['full_name'] ?? $fields['nombre_completo'] ?? $fields['first_name'] ?? $fields['nombre'] ?? trim(($fields['first_name'] ?? '') . ' ' . ($fields['last_name'] ?? '')) ?: 'Lead Facebook',
            'email' => $fields['email'] ?? $fields['correo'] ?? $fields['correo_electronico'] ?? null,
            'phone' => $fields['phone_number'] ?? $fields['telefono'] ?? $fields['número_de_teléfono'] ?? $fields['celular'] ?? null,
            'notes' => $this->buildNotesFromFields($fields),
        ];
    }

    private function buildNotesFromFields(array $fields): ?string
    {
        $excluded = ['full_name', 'nombre_completo', 'first_name', 'last_name', 'nombre', 'email', 'correo', 'correo_electronico', 'phone_number', 'telefono', 'número_de_teléfono', 'celular'];
        $extra = array_diff_key($fields, array_flip($excluded));

        if (empty($extra)) {
            return null;
        }

        $lines = [];
        foreach ($extra as $key => $value) {
            $lines[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
        }

        return implode("\n", $lines);
    }

    private function extractWhatsAppMessageText(array $message): ?string
    {
        return match ($message['type'] ?? 'text') {
            'text' => $message['text']['body'] ?? null,
            'image' => '[Imagen]' . (isset($message['image']['caption']) ? ' ' . $message['image']['caption'] : ''),
            'document' => '[Documento]' . (isset($message['document']['filename']) ? ' ' . $message['document']['filename'] : ''),
            'audio' => '[Audio]',
            'video' => '[Video]',
            'location' => '[Ubicación]',
            'contacts' => '[Contacto compartido]',
            'sticker' => '[Sticker]',
            default => '[' . ucfirst($message['type'] ?? 'Mensaje') . ']',
        };
    }

    private function fetchFacebookUserName(string $psid): ?string
    {
        $token = setting('crm_meta_page_access_token');
        if (! $token) {
            return null;
        }

        try {
            $response = Http::get("https://graph.facebook.com/v21.0/{$psid}", [
                'fields' => 'first_name,last_name',
                'access_token' => $token,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')) ?: null;
            }
        } catch (\Exception $e) {
            Log::warning('MetaLeadService: Could not fetch user name for PSID ' . $psid);
        }

        return null;
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }
}

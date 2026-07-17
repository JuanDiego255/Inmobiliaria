<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Models\WhatsAppBotFlow;
use Botble\RealEstate\Models\WhatsAppConversation;
use Illuminate\Support\Facades\Log;

class BotFlowService
{
    public function getActiveFlow(string $tenantId): ?WhatsAppBotFlow
    {
        return WhatsAppBotFlow::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();
    }

    public function getFlowState(string $phone, string $tenantId): ?array
    {
        $latest = WhatsAppConversation::where('phone', $phone)
            ->where('tenant_id', $tenantId)
            ->whereNotNull('metadata')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get();

        foreach ($latest as $conv) {
            $meta = $conv->metadata;
            if (isset($meta['flow_state'])) {
                return $meta;
            }
        }

        return null;
    }

    public function processMessage(string $message, WhatsAppBotFlow $flow, ?array $currentState): array
    {
        if (! $currentState || ($currentState['flow_state'] ?? '') === 'completed') {
            return $this->handleInitialMessage($message, $flow);
        }

        $state = $currentState['flow_state'] ?? '';

        if ($state === 'awaiting_option') {
            return $this->handleOptionSelection($message, $flow, $currentState);
        }

        if ($state === 'collecting') {
            return $this->handleStepResponse($message, $flow, $currentState);
        }

        return $this->handleInitialMessage($message, $flow);
    }

    protected function handleInitialMessage(string $message, WhatsAppBotFlow $flow): array
    {
        $options = $flow->flow_config['options'] ?? [];

        if (empty($options)) {
            return [
                'reply' => $flow->greeting_message ?: '¡Hola! ¿En qué puedo ayudarte?',
                'metadata' => null,
                'completed' => false,
            ];
        }

        $greeting = $flow->greeting_message ?: '¡Hola! ¿Qué trámite desea realizar?';
        $optionsList = '';
        foreach ($options as $option) {
            $optionsList .= "\n{$option['key']}. {$option['label']}";
        }

        return [
            'reply' => $greeting . $optionsList,
            'metadata' => [
                'flow_id' => $flow->id,
                'flow_state' => 'awaiting_option',
                'collected_data' => [],
            ],
            'completed' => false,
        ];
    }

    protected function handleOptionSelection(string $message, WhatsAppBotFlow $flow, array $state): array
    {
        $options = $flow->flow_config['options'] ?? [];
        $selected = $this->matchOption($message, $options);

        if (! $selected) {
            $optionsList = '';
            foreach ($options as $option) {
                $optionsList .= "\n{$option['key']}. {$option['label']}";
            }

            return [
                'reply' => "No entendí tu selección. Por favor elegí una opción:" . $optionsList,
                'metadata' => $state,
                'completed' => false,
            ];
        }

        $steps = $selected['steps'] ?? [];

        if (empty($steps)) {
            return [
                'reply' => $selected['completion_message'] ?? '¡Gracias! Un agente se comunicará con usted pronto.',
                'metadata' => [
                    'flow_id' => $flow->id,
                    'flow_state' => 'completed',
                    'selected_option' => $selected['key'],
                    'selected_label' => $selected['label'],
                    'intent' => $selected['intent'] ?? $selected['label'],
                    'current_step' => 0,
                    'collected_data' => [],
                ],
                'completed' => true,
            ];
        }

        $firstStep = $steps[0];

        $question = $firstStep['question'];
        if (($firstStep['type'] ?? '') === 'choice' && ! empty($firstStep['choices'])) {
            $question .= $this->formatChoices($firstStep['choices']);
        }

        return [
            'reply' => $question,
            'metadata' => [
                'flow_id' => $flow->id,
                'flow_state' => 'collecting',
                'selected_option' => $selected['key'],
                'selected_label' => $selected['label'],
                'intent' => $selected['intent'] ?? $selected['label'],
                'current_step' => 0,
                'collected_data' => [],
            ],
            'completed' => false,
        ];
    }

    protected function handleStepResponse(string $message, WhatsAppBotFlow $flow, array $state): array
    {
        $options = $flow->flow_config['options'] ?? [];
        $selectedKey = $state['selected_option'] ?? '';
        $currentStepIndex = $state['current_step'] ?? 0;
        $collectedData = $state['collected_data'] ?? [];

        $selectedOption = null;
        foreach ($options as $option) {
            if ($option['key'] === $selectedKey) {
                $selectedOption = $option;
                break;
            }
        }

        if (! $selectedOption) {
            return $this->handleInitialMessage($message, $flow);
        }

        $steps = $selectedOption['steps'] ?? [];
        $currentStep = $steps[$currentStepIndex] ?? null;

        if (! $currentStep) {
            return $this->completeFlow($state, $selectedOption);
        }

        $fieldName = $currentStep['id'] ?? "step_{$currentStepIndex}";
        $collectedData[$fieldName] = [
            'question' => $currentStep['question'] ?? '',
            'answer' => $message,
            'type' => $currentStep['type'] ?? 'free_text',
        ];

        $aiAfterStep = $selectedOption['ai_after_step'] ?? null;
        if ($aiAfterStep !== null && (int) $aiAfterStep === $currentStepIndex) {
            $state['collected_data'] = $collectedData;
            $state['flow_state'] = 'completed';
            $state['current_step'] = $currentStepIndex + 1;

            return [
                'reply' => '',
                'metadata' => $state,
                'completed' => true,
                'trigger_ai' => true,
            ];
        }

        $nextStepIndex = $currentStepIndex + 1;

        if ($nextStepIndex >= count($steps)) {
            $state['collected_data'] = $collectedData;
            $state['flow_state'] = 'completed';
            $state['current_step'] = $nextStepIndex;

            return [
                'reply' => $selectedOption['completion_message'] ?? '¡Gracias por la información! Un asesor revisará los detalles y se comunicará con usted pronto. 📋',
                'metadata' => $state,
                'completed' => true,
            ];
        }

        $nextStep = $steps[$nextStepIndex];
        $question = $nextStep['question'];
        if (($nextStep['type'] ?? '') === 'choice' && ! empty($nextStep['choices'])) {
            $question .= $this->formatChoices($nextStep['choices']);
        }

        $state['collected_data'] = $collectedData;
        $state['current_step'] = $nextStepIndex;

        return [
            'reply' => $question,
            'metadata' => $state,
            'completed' => false,
        ];
    }

    protected function completeFlow(array $state, array $option): array
    {
        $state['flow_state'] = 'completed';

        return [
            'reply' => $option['completion_message'] ?? '¡Gracias! Un agente se comunicará pronto.',
            'metadata' => $state,
            'completed' => true,
        ];
    }

    protected function matchOption(string $message, array $options): ?array
    {
        $message = trim($message);
        $messageLower = mb_strtolower($message);

        if (is_numeric($message)) {
            foreach ($options as $option) {
                if ($option['key'] === $message) {
                    return $option;
                }
            }
        }

        if (preg_match('/\b(\d+)\b/', $message, $matches)) {
            foreach ($options as $option) {
                if ($option['key'] === $matches[1]) {
                    return $option;
                }
            }
        }

        foreach ($options as $option) {
            $label = mb_strtolower($option['label'] ?? '');
            $intent = mb_strtolower($option['intent'] ?? '');

            if (str_contains($messageLower, $label) || ($intent && str_contains($messageLower, $intent))) {
                return $option;
            }

            $keywords = array_map('mb_strtolower', $option['keywords'] ?? []);
            foreach ($keywords as $keyword) {
                if (str_contains($messageLower, $keyword)) {
                    return $option;
                }
            }
        }

        return null;
    }

    protected function formatChoices(array $choices): string
    {
        $text = '';
        foreach ($choices as $i => $choice) {
            $num = $i + 1;
            $text .= "\n{$num}. {$choice}";
        }

        return $text;
    }

    public function formatCollectedDataForActivity(array $state): string
    {
        $text = "*Trámite:* {$state['selected_label']}\n";
        $text .= "*Intención:* {$state['intent']}\n\n";

        $collected = $state['collected_data'] ?? [];
        if (! empty($collected)) {
            $text .= "*Información recopilada:*\n";
            foreach ($collected as $field => $data) {
                $question = $data['question'] ?? $field;
                $answer = $data['answer'] ?? '';
                $text .= "• {$question}\n  ➜ {$answer}\n";
            }
        }

        return $text;
    }

    public function wantsToRestart(string $message): bool
    {
        $restartKeywords = ['reiniciar', 'volver', 'inicio', 'menú', 'menu', 'empezar', 'otra vez', 'reset'];
        $messageLower = mb_strtolower(trim($message));

        foreach ($restartKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                return true;
            }
        }

        return false;
    }
}

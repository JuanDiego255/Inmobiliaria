<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Models\TenantMailConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;

class TenantMailService
{
    public function getConfig(string $tenantId): ?TenantMailConfig
    {
        return TenantMailConfig::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();
    }

    public function sendLeadNotification(string $tenantId, array $data): bool
    {
        $config = $this->getConfig($tenantId);

        if (! $config) {
            Log::info('WhatsAppBot: No mail config for tenant, skipping email', ['tenant' => $tenantId]);

            return false;
        }

        $emails = $config->notification_emails ?? [];
        if (empty($emails)) {
            Log::info('WhatsAppBot: No notification emails configured', ['tenant' => $tenantId]);

            return false;
        }

        try {
            $this->sendWithConfig($config, $emails, $data);

            Log::info('WhatsAppBot: Lead notification sent', [
                'tenant' => $tenantId,
                'to' => $emails,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('WhatsAppBot: Failed to send lead notification', [
                'tenant' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function sendWithConfig(TenantMailConfig $config, array $recipients, array $data): void
    {
        $transport = (new EsmtpTransportFactory())->create(new Dsn(
            $config->mail_encryption === 'ssl' ? 'smtps' : 'smtp',
            $config->mail_host,
            $config->mail_username,
            $config->mail_password,
            $config->mail_port
        ));

        $mailer = new \Symfony\Component\Mailer\Mailer($transport);

        $fromAddress = $config->mail_from_address ?: $config->mail_username;
        $fromName = $config->mail_from_name ?: 'Bot WhatsApp';

        $subject = "🔔 Nuevo lead WhatsApp: {$data['client_name']} — {$data['intent']}";

        $body = $this->buildEmailBody($data);

        $email = (new \Symfony\Component\Mime\Email())
            ->from(new \Symfony\Component\Mime\Address($fromAddress, $fromName))
            ->subject($subject)
            ->html($body);

        foreach ($recipients as $recipient) {
            $email->addTo(trim($recipient));
        }

        $mailer->send($email);
    }

    protected function buildEmailBody(array $data): string
    {
        $clientName = htmlspecialchars($data['client_name'] ?? 'Sin nombre');
        $clientPhone = htmlspecialchars($data['client_phone'] ?? 'Sin teléfono');
        $intent = htmlspecialchars($data['intent'] ?? 'Consulta general');
        $optionLabel = htmlspecialchars($data['option_label'] ?? '');
        $collectedHtml = $this->formatCollectedDataHtml($data['collected_data'] ?? []);
        $date = now()->format('d/m/Y H:i');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
.header { background: #2563eb; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
.header h1 { margin: 0; font-size: 20px; }
.content { background: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; border-radius: 0 0 8px 8px; }
.field { margin-bottom: 12px; }
.field-label { font-weight: 600; color: #64748b; font-size: 12px; text-transform: uppercase; }
.field-value { font-size: 15px; margin-top: 2px; }
.collected { background: white; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; margin-top: 15px; }
.collected h3 { margin-top: 0; color: #1e40af; }
.qa-item { margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9; }
.qa-question { font-weight: 500; color: #475569; }
.qa-answer { color: #1e293b; margin-top: 2px; }
.footer { margin-top: 15px; font-size: 12px; color: #94a3b8; text-align: center; }
</style>
</head>
<body>
<div class="header">
<h1>🔔 Nuevo Lead desde WhatsApp</h1>
</div>
<div class="content">
<div class="field">
<div class="field-label">Cliente</div>
<div class="field-value">{$clientName}</div>
</div>
<div class="field">
<div class="field-label">Teléfono</div>
<div class="field-value"><a href="https://wa.me/{$clientPhone}">{$clientPhone}</a></div>
</div>
<div class="field">
<div class="field-label">Trámite solicitado</div>
<div class="field-value">{$optionLabel}</div>
</div>
<div class="field">
<div class="field-label">Fecha</div>
<div class="field-value">{$date}</div>
</div>
{$collectedHtml}
</div>
<div class="footer">
Generado automáticamente por el Bot de WhatsApp
</div>
</body>
</html>
HTML;
    }

    protected function formatCollectedDataHtml(array $collected): string
    {
        if (empty($collected)) {
            return '';
        }

        $items = '';
        foreach ($collected as $field => $data) {
            $question = htmlspecialchars($data['question'] ?? $field);
            $answer = htmlspecialchars($data['answer'] ?? '');
            $items .= <<<HTML
<div class="qa-item">
<div class="qa-question">{$question}</div>
<div class="qa-answer">➜ {$answer}</div>
</div>
HTML;
        }

        return <<<HTML
<div class="collected">
<h3>📋 Información recopilada</h3>
{$items}
</div>
HTML;
    }
}

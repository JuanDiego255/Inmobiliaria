<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\RealEstate\Jobs\ProcessMetaWebhook;
use Botble\RealEstate\Models\MetaWebhookLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class MetaWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = setting('crm_meta_verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken && $verifyToken) {
            Log::info('MetaWebhook: Verification successful.');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('MetaWebhook: Verification failed.', [
            'mode' => $mode,
            'token_match' => $token === $verifyToken,
        ]);

        return response('Forbidden', 403);
    }

    public function handle(Request $request): Response
    {
        if (! $this->validateSignature($request)) {
            Log::warning('MetaWebhook: Invalid signature.');
            return response('Invalid signature', 403);
        }

        $payload = $request->all();
        $object = $payload['object'] ?? 'unknown';

        $eventType = $this->determineEventType($payload);

        $autoImport = setting('crm_meta_auto_import');
        if (! $autoImport) {
            Log::info('MetaWebhook: Auto-import is disabled, logging only.', ['event_type' => $eventType]);

            MetaWebhookLog::query()->create([
                'event_type' => $eventType,
                'payload' => $payload,
                'processed' => false,
                'error_message' => 'Auto-import desactivado',
            ]);

            return response('OK', 200);
        }

        $log = MetaWebhookLog::query()->create([
            'event_type' => $eventType,
            'payload' => $payload,
        ]);

        ProcessMetaWebhook::dispatch($log);

        return response('OK', 200);
    }

    private function validateSignature(Request $request): bool
    {
        $appSecret = setting('crm_meta_app_secret');
        if (! $appSecret) {
            return false;
        }

        $signature = $request->header('X-Hub-Signature-256');
        if (! $signature) {
            return false;
        }

        $expectedHash = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expectedHash, $signature);
    }

    private function determineEventType(array $payload): string
    {
        $object = $payload['object'] ?? '';
        $entries = $payload['entry'] ?? [];

        if ($object === 'whatsapp_business_account') {
            return 'whatsapp';
        }

        if ($object === 'instagram') {
            return 'instagram';
        }

        if ($object === 'page') {
            foreach ($entries as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    if (($change['field'] ?? '') === 'leadgen') {
                        return 'leadgen';
                    }
                }

                if (! empty($entry['messaging'])) {
                    return 'messenger';
                }
            }

            return 'page_other';
        }

        return $object ?: 'unknown';
    }
}

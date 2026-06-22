<?php

namespace Botble\RealEstate\Jobs;

use Botble\RealEstate\Models\MetaWebhookLog;
use Botble\RealEstate\Services\MetaLeadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMetaWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public MetaWebhookLog $log
    ) {
    }

    public function handle(MetaLeadService $service): void
    {
        try {
            $payload = $this->log->payload;
            $entries = $payload['entry'] ?? [];

            foreach ($entries as $entry) {
                match ($this->log->event_type) {
                    'leadgen' => $service->processLeadgenEvent($entry),
                    'whatsapp' => $service->processWhatsAppMessage($entry),
                    'messenger' => $service->processMessengerEvent($entry),
                    'instagram' => $service->processInstagramDMEvent($entry),
                    default => Log::info('ProcessMetaWebhook: Unknown event type: ' . $this->log->event_type),
                };
            }

            $this->log->update(['processed' => true]);
        } catch (\Exception $e) {
            Log::error('ProcessMetaWebhook: Error processing log #' . $this->log->id, [
                'error' => $e->getMessage(),
            ]);

            $this->log->update([
                'processed' => false,
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
            ]);

            throw $e;
        }
    }
}

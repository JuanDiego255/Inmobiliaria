<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Jobs\ProcessMetaWebhook;
use Botble\RealEstate\Models\MetaWebhookLog;

class MetaWebhookLogController extends BaseController
{
    public function index(BaseHttpResponse $response)
    {
        $logs = MetaWebhookLog::query()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'event_type', 'processed', 'error_message', 'created_at']);

        return $response->setData($logs);
    }

    public function retry(int|string $id, BaseHttpResponse $response)
    {
        $log = MetaWebhookLog::query()->findOrFail($id);

        $log->update([
            'processed' => false,
            'error_message' => null,
        ]);

        ProcessMetaWebhook::dispatch($log);

        return $response->setMessage('Webhook re-enviado para procesamiento.');
    }
}

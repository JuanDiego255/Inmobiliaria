<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Models\BaseModel;

class MetaWebhookLog extends BaseModel
{
    protected $table = 're_meta_webhook_logs';

    protected $fillable = [
        'event_type',
        'payload',
        'processed',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed' => 'boolean',
    ];
}

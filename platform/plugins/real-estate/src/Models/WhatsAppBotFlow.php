<?php

namespace Botble\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppBotFlow extends Model
{
    protected $connection = 'mysql';

    protected $table = 're_whatsapp_bot_flows';

    protected $fillable = [
        'tenant_id',
        'name',
        'greeting_message',
        'flow_config',
        'is_active',
    ];

    protected $casts = [
        'flow_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function getOptionsAttribute(): array
    {
        return $this->flow_config['options'] ?? [];
    }
}

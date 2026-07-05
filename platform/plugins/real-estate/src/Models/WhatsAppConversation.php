<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppConversation extends BaseModel
{
    protected $connection = 'mysql';

    protected $table = 're_whatsapp_conversations';

    protected $fillable = [
        'phone',
        'tenant_id',
        'direction',
        'message',
        'lead_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }
}

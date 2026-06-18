<?php

namespace Botble\RealEstate\Models;

use Botble\ACL\Models\User;
use Botble\Base\Casts\SafeContent;
use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmReminder extends BaseModel
{
    protected $table = 're_crm_reminders';

    protected $fillable = [
        'title',
        'remind_at',
        'lead_id',
        'user_id',
        'is_dismissed',
    ];

    protected $casts = [
        'title' => SafeContent::class,
        'remind_at' => 'datetime',
        'is_dismissed' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }
}

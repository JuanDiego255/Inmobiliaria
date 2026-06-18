<?php

namespace Botble\RealEstate\Models;

use Botble\ACL\Models\User;
use Botble\Base\Casts\SafeContent;
use Botble\Base\Models\BaseModel;
use Botble\RealEstate\Enums\CrmActivityTypeEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmActivity extends BaseModel
{
    protected $table = 're_crm_activities';

    protected $fillable = [
        'lead_id',
        'user_id',
        'type',
        'description',
        'scheduled_at',
        'completed_at',
    ];

    protected $casts = [
        'type' => CrmActivityTypeEnum::class,
        'description' => SafeContent::class,
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace Botble\RealEstate\Models;

use Botble\ACL\Models\User;
use Botble\Base\Casts\SafeContent;
use Botble\Base\Models\BaseModel;
use Botble\RealEstate\Enums\CrmTaskPriorityEnum;
use Botble\RealEstate\Enums\CrmTaskStatusEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmTask extends BaseModel
{
    protected $table = 're_crm_tasks';

    protected $fillable = [
        'title',
        'description',
        'assigned_to',
        'lead_id',
        'property_id',
        'priority',
        'due_date',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'priority' => CrmTaskPriorityEnum::class,
        'status' => CrmTaskStatusEnum::class,
        'title' => SafeContent::class,
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}

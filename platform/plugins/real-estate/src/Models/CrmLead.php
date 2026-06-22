<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Casts\SafeContent;
use Botble\Base\Models\BaseModel;
use Botble\RealEstate\Enums\CrmLeadSourceEnum;
use Botble\RealEstate\Enums\CrmLeadStageEnum;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmLead extends BaseModel
{
    protected $table = 're_crm_leads';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'stage',
        'source',
        'budget_min',
        'budget_max',
        'currency_id',
        'client_id',
        'consult_id',
        'assigned_agent_id',
        'notes',
        'last_contacted_at',
        'expected_close_date',
        'lost_reason',
        'meta_lead_id',
        'meta_platform',
    ];

    protected $casts = [
        'stage' => CrmLeadStageEnum::class,
        'source' => CrmLeadSourceEnum::class,
        'name' => SafeContent::class,
        'notes' => SafeContent::class,
        'budget_min' => 'float',
        'budget_max' => 'float',
        'last_contacted_at' => 'datetime',
        'expected_close_date' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function consult(): BelongsTo
    {
        return $this->belongsTo(Consult::class);
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'assigned_agent_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 're_crm_lead_properties', 'lead_id', 'property_id')
            ->withPivot(['interest_level', 'notes'])
            ->withTimestamps();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class, 'lead_id');
    }
}

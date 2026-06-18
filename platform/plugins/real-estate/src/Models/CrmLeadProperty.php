<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmLeadProperty extends BaseModel
{
    protected $table = 're_crm_lead_properties';

    protected $fillable = [
        'lead_id',
        'property_id',
        'interest_level',
        'notes',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}

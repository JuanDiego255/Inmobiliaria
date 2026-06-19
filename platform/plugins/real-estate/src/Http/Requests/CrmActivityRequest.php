<?php

namespace Botble\RealEstate\Http\Requests;

use Botble\RealEstate\Enums\CrmActivityTypeEnum;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class CrmActivityRequest extends Request
{
    public function rules(): array
    {
        return [
            'lead_id' => ['required', 'exists:re_crm_leads,id'],
            'type' => ['required', Rule::in(array_keys(CrmActivityTypeEnum::labels()))],
            'description' => ['required', 'string', 'max:5000'],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }
}

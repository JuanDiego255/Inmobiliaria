<?php

namespace Botble\RealEstate\Http\Requests;

use Botble\RealEstate\Enums\CrmTaskPriorityEnum;
use Botble\RealEstate\Enums\CrmTaskStatusEnum;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class CrmTaskRequest extends Request
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'lead_id' => ['nullable', 'exists:re_crm_leads,id'],
            'property_id' => ['nullable', 'exists:re_properties,id'],
            'priority' => ['nullable', Rule::in(CrmTaskPriorityEnum::values())],
            'status' => ['nullable', Rule::in(CrmTaskStatusEnum::values())],
            'due_date' => ['nullable', 'date'],
        ];
    }
}

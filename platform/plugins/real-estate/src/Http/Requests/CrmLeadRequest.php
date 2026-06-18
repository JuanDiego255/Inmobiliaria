<?php

namespace Botble\RealEstate\Http\Requests;

use Botble\RealEstate\Enums\CrmLeadSourceEnum;
use Botble\RealEstate\Enums\CrmLeadStageEnum;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class CrmLeadRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:25'],
            'stage' => ['nullable', Rule::in(CrmLeadStageEnum::values())],
            'source' => ['nullable', Rule::in(CrmLeadSourceEnum::values())],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'min:0'],
            'assigned_agent_id' => ['nullable', 'exists:re_accounts,id'],
            'client_id' => ['nullable', 'exists:re_clients,id'],
            'consult_id' => ['nullable', 'exists:re_consults,id'],
            'currency_id' => ['nullable', 'exists:re_currencies,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'expected_close_date' => ['nullable', 'date'],
            'lost_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}

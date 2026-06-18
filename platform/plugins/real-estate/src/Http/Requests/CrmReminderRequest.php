<?php

namespace Botble\RealEstate\Http\Requests;

use Botble\Support\Http\Requests\Request;

class CrmReminderRequest extends Request
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:300'],
            'remind_at' => ['required', 'date', 'after:now'],
            'lead_id' => ['nullable', 'exists:re_crm_leads,id'],
        ];
    }
}

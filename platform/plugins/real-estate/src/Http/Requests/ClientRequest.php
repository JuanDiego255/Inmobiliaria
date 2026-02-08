<?php

namespace Botble\RealEstate\Http\Requests;

use Botble\RealEstate\Enums\ClientStatusEnum;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class ClientRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:25'],
            'address' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'age' => ['nullable', 'integer', 'min:1', 'max:150'],
            'status' => Rule::in(ClientStatusEnum::values()),
        ];
    }
}

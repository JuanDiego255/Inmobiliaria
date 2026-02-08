<?php

namespace Botble\RealEstate\Http\Requests;

use Botble\RealEstate\Enums\BoardStatusEnum;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class BoardRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'client_id' => ['required', 'exists:re_clients,id'],
            'status' => Rule::in(BoardStatusEnum::values()),
        ];
    }
}

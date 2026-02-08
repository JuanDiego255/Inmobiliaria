<?php

namespace Botble\RealEstate\Http\Requests;

use Botble\RealEstate\Enums\CouponTypeEnum;
use Botble\RealEstate\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'quantity' => $this->has('is_unlimited') ? null : $this->input('quantity'),
            'expires_date' => $this->has('never_expired') ? null : $this->input('expires_date'),
        ]);
    }

    public function rules(): array
    {
        $valueRules = [
            'required',
            'numeric',
            'min:1',
        ];

        if ($this->input('type') === CouponTypeEnum::PERCENTAGE) {
            $valueRules[] = 'max:100';
        }

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique(Coupon::class, 'code')->ignore($this->route('coupon')),
            ],
            'type' => ['required', 'string', Rule::in(CouponTypeEnum::values())],
            'value' => $valueRules,
            'quantity' => ['nullable', 'numeric', 'min:1'],
            'expires_date' => ['nullable', 'date_format:Y-m-d'],
            'expires_time' => ['nullable', 'date_format:H:i'],
        ];
    }
}

<?php

namespace Botble\RealEstate\Http\Requests;

use Botble\Base\Facades\BaseHelper;
use Botble\Captcha\Facades\Captcha;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\Support\Http\Requests\Request;
use Illuminate\Support\Arr;

class SendConsultRequest extends Request
{
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:220'],
            'email' => ['nullable', 'email'],
            'phone' => 'nullable|string|' . BaseHelper::getPhoneValidationRule(),
            'content' => ['required', 'string'],
        ];

        if (is_plugin_active('captcha')) {
            $rules += Captcha::rules();
        }

        $availableMandatoryFields = RealEstateHelper::enabledMandatoryFieldsAtConsultForm();
        $hiddenFields = RealEstateHelper::getHiddenFieldsAtConsultForm();

        if ($hiddenFields) {
            Arr::forget($rules, $hiddenFields);
        }

        if ($availableMandatoryFields) {
            foreach ($availableMandatoryFields as $value) {
                if (! isset($rules[$value])) {
                    continue;
                }

                if (is_string($rules[$value])) {
                    $rules[$value] = str_replace('nullable', 'required', $rules[$value]);

                    continue;
                }

                if (is_array($rules[$value])) {
                    $rules[$value] = array_merge(['required'], array_filter($rules[$value], fn ($item) => $item !== 'nullable'));
                }
            }
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'name' => __('Name'),
            'email' => __('Email'),
            'content' => __('Content'),
        ] + (is_plugin_active('captcha') ? Captcha::attributes() : []);
    }
}

<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Base\Facades\PageTitle;
use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Models\Account;
use Botble\Setting\Facades\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MetaLeadSettingsController extends BaseController
{
    public function index()
    {
        PageTitle::setTitle('CRM - Meta Integración');

        Assets::addStylesDirectly([
            'vendor/core/plugins/real-estate/css/crm.css',
            'vendor/core/plugins/real-estate/css/crm-dashboard.css',
        ]);

        $agents = Account::query()
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get();

        if (! setting('crm_meta_verify_token')) {
            Setting::set('crm_meta_verify_token', 'crm_verify_' . Str::random(24));
            Setting::save();
        }

        return view('plugins/real-estate::crm.meta-settings', compact('agents'));
    }

    public function help()
    {
        PageTitle::setTitle('CRM - Ayuda Meta API');

        Assets::addStylesDirectly([
            'vendor/core/plugins/real-estate/css/crm.css',
            'vendor/core/plugins/real-estate/css/crm-dashboard.css',
        ]);

        return view('plugins/real-estate::crm.meta-help');
    }

    public function update(Request $request)
    {
        $request->validate([
            'crm_meta_app_id' => ['nullable', 'string', 'max:100'],
            'crm_meta_app_secret' => ['nullable', 'string', 'max:200'],
            'crm_meta_page_access_token' => ['nullable', 'string', 'max:500'],
            'crm_meta_verify_token' => ['nullable', 'string', 'max:100'],
            'crm_meta_whatsapp_phone_id' => ['nullable', 'string', 'max:100'],
            'crm_meta_whatsapp_biz_id' => ['nullable', 'string', 'max:100'],
            'crm_meta_default_agent_id' => ['nullable', 'integer'],
            'crm_meta_auto_import' => ['nullable'],
            'crm_whatsapp_bot_enabled' => ['nullable'],
            'crm_whatsapp_bot_llm_provider' => ['nullable', 'string', 'in:claude,openai'],
            'crm_whatsapp_bot_llm_model' => ['nullable', 'string', 'max:100'],
            'crm_whatsapp_bot_llm_api_key' => ['nullable', 'string', 'max:500'],
            'crm_whatsapp_bot_system_prompt' => ['nullable', 'string', 'max:5000'],
            'crm_whatsapp_bot_max_properties' => ['nullable', 'integer', 'min:1', 'max:10'],
            'crm_whatsapp_bot_welcome_message' => ['nullable', 'string', 'max:500'],
        ]);

        $keys = [
            'crm_meta_app_id',
            'crm_meta_app_secret',
            'crm_meta_page_access_token',
            'crm_meta_verify_token',
            'crm_meta_whatsapp_phone_id',
            'crm_meta_whatsapp_biz_id',
            'crm_meta_default_agent_id',
            'crm_whatsapp_bot_llm_provider',
            'crm_whatsapp_bot_llm_model',
            'crm_whatsapp_bot_llm_api_key',
            'crm_whatsapp_bot_system_prompt',
            'crm_whatsapp_bot_max_properties',
            'crm_whatsapp_bot_welcome_message',
        ];

        foreach ($keys as $key) {
            Setting::set($key, $request->input($key, ''));
        }

        Setting::set('crm_meta_auto_import', $request->input('crm_meta_auto_import', 0) ? '1' : '0');
        Setting::set('crm_whatsapp_bot_enabled', $request->input('crm_whatsapp_bot_enabled', 0) ? '1' : '0');
        Setting::save();

        return redirect()
            ->route('crm.meta-settings')
            ->with('success_msg', 'Configuración de Meta guardada correctamente.');
    }
}

<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Models\TenantMailConfig;
use Botble\RealEstate\Models\WhatsAppBotFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BotFlowController extends BaseController
{
    public function index(Request $request)
    {
        $tenants = DB::connection('mysql')->table('tenants')->orderBy('name')->get();
        $tenantId = $request->query('tenant', $tenants->first()?->id);

        $flow = $tenantId ? WhatsAppBotFlow::where('tenant_id', $tenantId)->first() : null;
        $mailConfig = $tenantId ? TenantMailConfig::where('tenant_id', $tenantId)->first() : null;

        page_title()->setTitle('Entrenamiento del Bot');

        return view('plugins/real-estate::crm.bot-flow', compact('flow', 'mailConfig', 'tenantId', 'tenants'));
    }

    public function saveFlow(Request $request)
    {
        $tenantId = $request->input('tenant_id');

        if (! $tenantId) {
            return redirect()->back()->with('error_msg', 'Seleccioná un tenant.');
        }

        $validator = Validator::make($request->all(), [
            'greeting_message' => 'required|string|max:500',
            'flow_config' => 'required|json',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $flowConfig = json_decode($request->input('flow_config'), true);

        if (! $this->validateFlowConfig($flowConfig)) {
            return redirect()->back()
                ->with('error_msg', 'La configuración del flujo no es válida. Verificá la estructura JSON.')
                ->withInput();
        }

        WhatsAppBotFlow::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'name' => $request->input('name', 'Flujo principal'),
                'greeting_message' => $request->input('greeting_message'),
                'flow_config' => $flowConfig,
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        return redirect()
            ->route('crm.bot-flow.index', ['tenant' => $tenantId])
            ->with('success_msg', '¡Flujo del bot guardado correctamente!');
    }

    public function saveMailConfig(Request $request)
    {
        $tenantId = $request->input('tenant_id');

        if (! $tenantId) {
            return redirect()->back()->with('error_msg', 'Seleccioná un tenant.');
        }

        $validator = Validator::make($request->all(), [
            'mail_host' => 'required|string',
            'mail_port' => 'required|integer|min:1|max:65535',
            'mail_username' => 'required|email',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'required|in:tls,ssl,none',
            'mail_from_address' => 'nullable|email',
            'mail_from_name' => 'nullable|string|max:100',
            'notification_emails' => 'required|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $notificationEmails = array_filter(
            array_map('trim', explode(',', $request->input('notification_emails')))
        );

        $data = [
            'mail_driver' => 'smtp',
            'mail_host' => $request->input('mail_host'),
            'mail_port' => (int) $request->input('mail_port'),
            'mail_username' => $request->input('mail_username'),
            'mail_encryption' => $request->input('mail_encryption'),
            'mail_from_address' => $request->input('mail_from_address') ?: $request->input('mail_username'),
            'mail_from_name' => $request->input('mail_from_name') ?: 'Bot WhatsApp',
            'notification_emails' => $notificationEmails,
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($request->filled('mail_password')) {
            $data['mail_password'] = $request->input('mail_password');
        }

        TenantMailConfig::updateOrCreate(
            ['tenant_id' => $tenantId],
            $data
        );

        return redirect()
            ->route('crm.bot-flow.index', ['tenant' => $tenantId])
            ->with('success_msg', '¡Configuración de correo guardada correctamente!');
    }

    public function testMail(Request $request)
    {
        $tenantId = $request->input('tenant_id');

        if (! $tenantId) {
            return response()->json(['success' => false, 'message' => 'Seleccioná un tenant.']);
        }

        $config = TenantMailConfig::where('tenant_id', $tenantId)->first();

        if (! $config || ! $config->is_active) {
            return response()->json(['success' => false, 'message' => 'Configurá y activá el correo primero.']);
        }

        try {
            $mailService = app(\Botble\RealEstate\Services\TenantMailService::class);
            $mailService->sendLeadNotification($tenantId, [
                'client_name' => 'Cliente de Prueba',
                'client_phone' => '50600000000',
                'intent' => 'Test',
                'option_label' => 'Correo de prueba',
                'collected_data' => [
                    'test' => ['question' => '¿Funciona el correo?', 'answer' => 'Sí, este es un correo de prueba.'],
                ],
            ]);

            return response()->json(['success' => true, 'message' => '¡Correo de prueba enviado!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    protected function validateFlowConfig(array $config): bool
    {
        if (! isset($config['options']) || ! is_array($config['options'])) {
            return false;
        }

        foreach ($config['options'] as $option) {
            if (empty($option['key']) || empty($option['label'])) {
                return false;
            }

            if (isset($option['steps']) && is_array($option['steps'])) {
                foreach ($option['steps'] as $step) {
                    if (empty($step['id']) || empty($step['question'])) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}

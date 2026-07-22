<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Base\Facades\PageTitle;
use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Services\GoogleCalendarService;
use Botble\Setting\Facades\Setting;
use Illuminate\Http\Request;

class CrmSettingsController extends BaseController
{
    public function index(GoogleCalendarService $gcal)
    {
        PageTitle::setTitle('CRM - Configuración');

        Assets::addStylesDirectly([
            'vendor/core/plugins/real-estate/css/crm.css',
            'vendor/core/plugins/real-estate/css/crm-dashboard.css',
        ]);

        $isConnected = $gcal->isConfigured();
        $authUrl = null;

        if (! $isConnected && setting('crm_gcal_client_id') && setting('crm_gcal_client_secret')) {
            try {
                $authUrl = $gcal->getAuthUrl();
            } catch (\Exception $e) {
                // Client ID/Secret may be invalid
            }
        }

        return view('plugins/real-estate::crm.settings', compact('isConnected', 'authUrl'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'crm_gcal_client_id' => ['nullable', 'string', 'max:200'],
            'crm_gcal_client_secret' => ['nullable', 'string', 'max:200'],
            'crm_gcal_calendar_id' => ['nullable', 'string', 'max:200'],
        ]);

        $keys = [
            'crm_gcal_client_id',
            'crm_gcal_client_secret',
            'crm_gcal_calendar_id',
        ];

        foreach ($keys as $key) {
            Setting::set($key, $request->input($key, ''));
        }

        Setting::set('crm_gcal_sync_tasks', $request->input('crm_gcal_sync_tasks', 0) ? '1' : '0');
        Setting::set('crm_gcal_sync_reminders', $request->input('crm_gcal_sync_reminders', 0) ? '1' : '0');
        Setting::save();

        return redirect()
            ->route('crm.settings.index')
            ->with('success_msg', 'Configuración guardada correctamente.');
    }

    public function googleCalendarCallback(Request $request, GoogleCalendarService $gcal)
    {
        try {
            $code = $request->query('code');

            if (! $code) {
                return redirect()
                    ->route('crm.settings.index')
                    ->with('error_msg', 'No se recibió código de autorización de Google.');
            }

            $success = $gcal->handleCallback($code);

            return redirect()
                ->route('crm.settings.index')
                ->with(
                    $success ? 'success_msg' : 'error_msg',
                    $success
                        ? 'Google Calendar conectado correctamente.'
                        : 'Error al conectar Google Calendar. Verificá las credenciales.'
                );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('GCal callback error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()
                ->route('crm.settings.index')
                ->with('error_msg', 'Error: ' . $e->getMessage());
        }
    }

    public function googleCalendarManualCode(Request $request, GoogleCalendarService $gcal)
    {
        try {
            $input = trim($request->input('auth_code_url', ''));

            if (! $input) {
                return redirect()
                    ->route('crm.settings.index')
                    ->with('error_msg', 'Pegá la URL o el código de autorización.');
            }

            $code = $input;
            if (str_contains($input, 'code=')) {
                parse_str(parse_url($input, PHP_URL_QUERY) ?: '', $params);
                $code = $params['code'] ?? '';
            }

            if (! $code) {
                return redirect()
                    ->route('crm.settings.index')
                    ->with('error_msg', 'No se pudo extraer el código de autorización de la URL proporcionada.');
            }

            $success = $gcal->handleCallback($code);

            return redirect()
                ->route('crm.settings.index')
                ->with(
                    $success ? 'success_msg' : 'error_msg',
                    $success
                        ? 'Google Calendar conectado correctamente.'
                        : 'Error al conectar Google Calendar. Verificá las credenciales.'
                );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('GCal manual code error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()
                ->route('crm.settings.index')
                ->with('error_msg', 'Error: ' . $e->getMessage());
        }
    }

    public function googleCalendarDisconnect(GoogleCalendarService $gcal)
    {
        $gcal->revokeAccess();

        return redirect()
            ->route('crm.settings.index')
            ->with('success_msg', 'Google Calendar desconectado.');
    }
}

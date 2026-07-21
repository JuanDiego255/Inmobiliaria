<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Base\Facades\PageTitle;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Http\Requests\CrmReminderRequest;
use Botble\RealEstate\Models\CrmReminder;
use Botble\RealEstate\Services\GoogleCalendarService;
use Botble\RealEstate\Tables\CrmReminderTable;
use Carbon\Carbon;

class CrmReminderController extends BaseController
{
    public function index(CrmReminderTable $table)
    {
        PageTitle::setTitle('CRM - Recordatorios');
        Assets::addStylesDirectly(['vendor/core/plugins/real-estate/css/crm-dashboard.css']);
        Assets::addScriptsDirectly(['vendor/core/plugins/real-estate/js/crm-modals.js']);
        $table->setView('plugins/real-estate::crm.reminders');
        return $table->renderTable();
    }

    public function store(CrmReminderRequest $request, BaseHttpResponse $response)
    {
        $reminder = CrmReminder::query()->create(array_merge(
            $request->validated(),
            ['user_id' => auth()->id()]
        ));

        app(GoogleCalendarService::class)->createEventFromReminder($reminder->load(['lead']));

        return $response
            ->setMessage('Recordatorio creado correctamente.')
            ->setData($reminder);
    }

    public function pending(BaseHttpResponse $response)
    {
        $reminders = CrmReminder::query()
            ->with('lead')
            ->where('user_id', auth()->id())
            ->where('is_dismissed', false)
            ->where('remind_at', '<=', Carbon::now())
            ->orderBy('remind_at', 'desc')
            ->limit(20)
            ->get();

        return $response->setData($reminders);
    }

    public function dismiss(int|string $id, BaseHttpResponse $response)
    {
        $reminder = CrmReminder::query()
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        $reminder->update(['is_dismissed' => true]);

        return $response->setMessage('Recordatorio descartado.');
    }

    public function dismissAll(BaseHttpResponse $response)
    {
        CrmReminder::query()
            ->where('user_id', auth()->id())
            ->where('is_dismissed', false)
            ->where('remind_at', '<=', Carbon::now())
            ->update(['is_dismissed' => true]);

        return $response->setMessage('Todos los recordatorios descartados.');
    }

    public function destroy(int $id)
    {
        $reminder = CrmReminder::findOrFail($id);
        $reminder->delete();
        return $this->httpResponse()->setMessage('Recordatorio eliminado.');
    }
}

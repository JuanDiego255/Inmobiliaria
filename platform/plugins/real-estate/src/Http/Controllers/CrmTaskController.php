<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\ACL\Models\User;
use Botble\Base\Facades\Assets;
use Botble\Base\Facades\PageTitle;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Enums\CrmTaskStatusEnum;
use Botble\RealEstate\Http\Requests\CrmTaskRequest;
use Botble\RealEstate\Models\CrmTask;
use Botble\RealEstate\Services\GoogleCalendarService;
use Botble\RealEstate\Tables\CrmTaskTable;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class CrmTaskController extends BaseController
{
    public function index(CrmTaskTable $table)
    {
        PageTitle::setTitle('CRM - Tareas');
        Assets::addStylesDirectly(['vendor/core/plugins/real-estate/css/crm-dashboard.css']);
        Assets::addScriptsDirectly(['vendor/core/plugins/real-estate/js/crm-modals.js', 'vendor/core/plugins/real-estate/js/crm-tasks.js']);

        $users = User::query()->select('id', 'first_name', 'last_name', 'email')->get();

        $table->setView('plugins/real-estate::crm.tasks');

        return $table->renderTable(compact('users'));
    }

    public function store(CrmTaskRequest $request, BaseHttpResponse $response)
    {
        $task = CrmTask::query()->create($request->validated());

        app(GoogleCalendarService::class)->createEventFromTask($task->load(['lead']));

        return $response
            ->setMessage('Tarea creada correctamente.')
            ->setData($task->load(['assignedUser', 'lead']));
    }

    public function update(int|string $id, CrmTaskRequest $request, BaseHttpResponse $response)
    {
        $task = CrmTask::query()->findOrFail($id);
        $task->fill($request->validated());
        $task->save();

        return $response
            ->setMessage('Tarea actualizada correctamente.')
            ->setData($task->load(['assignedUser', 'lead']));
    }

    public function destroy(int|string $id, BaseHttpResponse $response)
    {
        try {
            CrmTask::query()->findOrFail($id)->delete();
            return $response->setMessage('Tarea eliminada correctamente.');
        } catch (Exception $exception) {
            return $response->setError()->setMessage($exception->getMessage());
        }
    }

    public function updateStatus(int|string $id, Request $request, BaseHttpResponse $response)
    {
        $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys(CrmTaskStatusEnum::labels()))],
        ]);

        $task = CrmTask::query()->findOrFail($id);
        $data = ['status' => $request->input('status')];

        if ($request->input('status') === CrmTaskStatusEnum::COMPLETED) {
            $data['completed_at'] = Carbon::now();
        }

        $task->update($data);

        return $response
            ->setMessage('Estado de tarea actualizado.')
            ->setData($task);
    }

    public function myTasks(BaseHttpResponse $response)
    {
        $tasks = CrmTask::query()
            ->with(['lead'])
            ->where('assigned_to', auth()->id())
            ->whereIn('status', [CrmTaskStatusEnum::PENDING, CrmTaskStatusEnum::IN_PROGRESS])
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END, due_date ASC')
            ->limit(15)
            ->get();

        return $response->setData($tasks);
    }
}

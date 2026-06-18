<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Enums\CrmTaskStatusEnum;
use Botble\RealEstate\Http\Requests\CrmTaskRequest;
use Botble\RealEstate\Models\CrmTask;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class CrmTaskController extends BaseController
{
    public function index(Request $request, BaseHttpResponse $response)
    {
        $query = CrmTask::query()
            ->with(['assignedUser', 'lead'])
            ->orderByRaw("FIELD(status, 'pending', 'in_progress', 'completed', 'cancelled')")
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END, due_date ASC')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->input('assigned_to'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        $tasks = $query->paginate(50);

        return $response->setData($tasks);
    }

    public function store(CrmTaskRequest $request, BaseHttpResponse $response)
    {
        $task = CrmTask::query()->create($request->validated());

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
            'status' => ['required', 'in:' . implode(',', CrmTaskStatusEnum::values())],
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

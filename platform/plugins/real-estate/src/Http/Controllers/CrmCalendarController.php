<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\ACL\Models\User;
use Botble\Base\Facades\Assets;
use Botble\Base\Facades\PageTitle;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\RealEstate\Models\CrmActivity;
use Botble\RealEstate\Models\CrmLead;
use Botble\RealEstate\Models\CrmReminder;
use Botble\RealEstate\Models\CrmTask;
use Botble\RealEstate\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CrmCalendarController extends BaseController
{
    public function index()
    {
        PageTitle::setTitle('CRM - Calendario');

        Assets::addStylesDirectly([
            'vendor/core/plugins/real-estate/css/crm.css',
            'vendor/core/plugins/real-estate/css/crm-dashboard.css',
            'vendor/core/plugins/real-estate/css/crm-calendar.css',
        ]);
        Assets::addScriptsDirectly([
            'vendor/core/plugins/real-estate/js/crm-modals.js',
        ]);

        $gcal = app(GoogleCalendarService::class);
        $isGcalConnected = $gcal->isConfigured();

        $users = User::query()->select('id', 'first_name', 'last_name')->orderBy('first_name')->get();

        return view('plugins/real-estate::crm.calendar', compact('isGcalConnected', 'users'));
    }

    public function events(Request $request, BaseHttpResponse $response)
    {
        $start = $request->input('start', now()->startOfMonth()->toDateString());
        $end = $request->input('end', now()->endOfMonth()->toDateString());
        $sources = $request->input('sources', ['tasks', 'reminders', 'activities', 'leads']);

        if (is_string($sources)) {
            $sources = explode(',', $sources);
        }

        $events = [];

        if (in_array('tasks', $sources)) {
            $tasks = CrmTask::query()
                ->with(['lead', 'assignedUser'])
                ->whereNotNull('due_date')
                ->where('due_date', '>=', $start)
                ->where('due_date', '<=', $end)
                ->get();

            foreach ($tasks as $task) {
                $color = match ($task->status?->getValue() ?? $task->status) {
                    'completed' => '#43a047',
                    'in_progress' => '#1565c0',
                    'cancelled' => '#9e9e9e',
                    default => '#e65100',
                };

                $events[] = [
                    'id' => 'task_' . $task->id,
                    'crm_id' => $task->id,
                    'title' => $task->title,
                    'start' => $task->due_date->format('Y-m-d'),
                    'allDay' => true,
                    'source' => 'task',
                    'color' => $color,
                    'textColor' => '#fff',
                    'extendedProps' => [
                        'type' => 'task',
                        'status' => $task->status?->getValue() ?? $task->status,
                        'priority' => $task->priority?->getValue() ?? $task->priority,
                        'description' => $task->description,
                        'lead_name' => $task->lead?->name,
                        'assigned_to' => $task->assignedUser ? ($task->assignedUser->first_name . ' ' . $task->assignedUser->last_name) : null,
                        'google_event_id' => $task->google_event_id,
                    ],
                ];
            }
        }

        if (in_array('reminders', $sources)) {
            $reminders = CrmReminder::query()
                ->with(['lead'])
                ->whereNotNull('remind_at')
                ->where('remind_at', '>=', $start)
                ->where('remind_at', '<=', $end)
                ->get();

            foreach ($reminders as $reminder) {
                $events[] = [
                    'id' => 'reminder_' . $reminder->id,
                    'crm_id' => $reminder->id,
                    'title' => $reminder->title,
                    'start' => $reminder->remind_at->toIso8601String(),
                    'allDay' => false,
                    'source' => 'reminder',
                    'color' => $reminder->is_dismissed ? '#9e9e9e' : '#7b1fa2',
                    'textColor' => '#fff',
                    'extendedProps' => [
                        'type' => 'reminder',
                        'is_dismissed' => $reminder->is_dismissed,
                        'lead_name' => $reminder->lead?->name,
                        'google_event_id' => $reminder->google_event_id,
                    ],
                ];
            }
        }

        if (in_array('activities', $sources)) {
            $activities = CrmActivity::query()
                ->with(['lead', 'user'])
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '>=', $start)
                ->where('scheduled_at', '<=', $end)
                ->get();

            foreach ($activities as $activity) {
                $events[] = [
                    'id' => 'activity_' . $activity->id,
                    'crm_id' => $activity->id,
                    'title' => ($activity->type?->getValue() ?? $activity->type) . ': ' . mb_substr($activity->description, 0, 60),
                    'start' => $activity->scheduled_at->toIso8601String(),
                    'end' => $activity->completed_at?->toIso8601String(),
                    'allDay' => false,
                    'source' => 'activity',
                    'color' => $activity->completed_at ? '#66bb6a' : '#0288d1',
                    'textColor' => '#fff',
                    'extendedProps' => [
                        'type' => 'activity',
                        'activity_type' => $activity->type?->getValue() ?? $activity->type,
                        'description' => $activity->description,
                        'lead_name' => $activity->lead?->name,
                        'completed' => (bool) $activity->completed_at,
                    ],
                ];
            }
        }

        if (in_array('leads', $sources)) {
            $leads = CrmLead::query()
                ->whereNotNull('expected_close_date')
                ->where('expected_close_date', '>=', $start)
                ->where('expected_close_date', '<=', $end)
                ->whereNotIn('stage', ['ganado', 'perdido'])
                ->get();

            foreach ($leads as $lead) {
                $events[] = [
                    'id' => 'lead_' . $lead->id,
                    'crm_id' => $lead->id,
                    'title' => 'Cierre: ' . $lead->name,
                    'start' => $lead->expected_close_date->format('Y-m-d'),
                    'allDay' => true,
                    'source' => 'lead',
                    'color' => '#00897b',
                    'textColor' => '#fff',
                    'editable' => false,
                    'extendedProps' => [
                        'type' => 'lead',
                        'stage' => $lead->stage?->getValue() ?? $lead->stage,
                        'lead_name' => $lead->name,
                    ],
                ];
            }
        }

        // Google Calendar events
        $gcal = app(GoogleCalendarService::class);
        if ($gcal->isConfigured() && $request->input('include_google', true)) {
            $googleEvents = $gcal->listEvents($start, $end);
            $events = array_merge($events, $googleEvents);
        }

        return $response->setData($events);
    }

    public function updateEvent(Request $request, BaseHttpResponse $response)
    {
        $request->validate([
            'id' => ['required', 'string'],
            'start' => ['required', 'string'],
            'end' => ['nullable', 'string'],
        ]);

        $rawId = $request->input('id');
        $newStart = $request->input('start');
        $newEnd = $request->input('end');

        [$type, $id] = $this->parseEventId($rawId);

        $gcal = app(GoogleCalendarService::class);

        switch ($type) {
            case 'task':
                $task = CrmTask::query()->findOrFail($id);
                $date = Carbon::parse($newStart)->format('Y-m-d');
                $task->update(['due_date' => $date]);

                if ($task->google_event_id) {
                    $gcal->updateEvent($task->google_event_id, ['start' => $date, 'end' => $date]);
                }

                return $response->setMessage('Tarea actualizada.')->setData(['success' => true]);

            case 'reminder':
                $reminder = CrmReminder::query()->findOrFail($id);
                $reminder->update(['remind_at' => Carbon::parse($newStart)]);

                if ($reminder->google_event_id) {
                    $endTime = $newEnd ?: Carbon::parse($newStart)->addMinutes(30)->toIso8601String();
                    $gcal->updateEvent($reminder->google_event_id, ['start' => $newStart, 'end' => $endTime]);
                }

                return $response->setMessage('Recordatorio actualizado.')->setData(['success' => true]);

            case 'gcal':
                $googleEventId = str_replace('gcal_', '', $rawId);
                $gcal->updateEvent($googleEventId, [
                    'start' => $newStart,
                    'end' => $newEnd ?: $newStart,
                ]);

                return $response->setMessage('Evento de Google Calendar actualizado.')->setData(['success' => true]);

            default:
                return $response->setError()->setMessage('No se puede mover este tipo de evento.');
        }
    }

    public function storeEvent(Request $request, BaseHttpResponse $response)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:300'],
            'start' => ['required', 'string'],
            'end' => ['nullable', 'string'],
            'type' => ['required', 'in:task,reminder'],
            'lead_id' => ['nullable', 'integer'],
            'assigned_to' => ['nullable', 'integer'],
            'priority' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $gcal = app(GoogleCalendarService::class);

        if ($request->input('type') === 'task') {
            $task = CrmTask::query()->create([
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'due_date' => Carbon::parse($request->input('start'))->format('Y-m-d'),
                'lead_id' => $request->input('lead_id'),
                'assigned_to' => $request->input('assigned_to'),
                'priority' => $request->input('priority', 'medium'),
                'status' => 'pending',
            ]);

            $gcal->createEventFromTask($task->load('lead'));

            return $response->setMessage('Tarea creada.')->setData($task);
        }

        $reminder = CrmReminder::query()->create([
            'title' => $request->input('title'),
            'remind_at' => Carbon::parse($request->input('start')),
            'lead_id' => $request->input('lead_id'),
            'user_id' => auth()->id(),
        ]);

        $gcal->createEventFromReminder($reminder->load('lead'));

        return $response->setMessage('Recordatorio creado.')->setData($reminder);
    }

    public function deleteEvent(string $id, BaseHttpResponse $response)
    {
        [$type, $modelId] = $this->parseEventId($id);

        $gcal = app(GoogleCalendarService::class);

        switch ($type) {
            case 'task':
                $task = CrmTask::query()->findOrFail($modelId);
                if ($task->google_event_id) {
                    $gcal->deleteEvent($task->google_event_id);
                }
                $task->delete();
                return $response->setMessage('Tarea eliminada.');

            case 'reminder':
                $reminder = CrmReminder::query()->findOrFail($modelId);
                if ($reminder->google_event_id) {
                    $gcal->deleteEvent($reminder->google_event_id);
                }
                $reminder->delete();
                return $response->setMessage('Recordatorio eliminado.');

            case 'gcal':
                $googleEventId = str_replace('gcal_', '', $id);
                $gcal->deleteEvent($googleEventId);
                return $response->setMessage('Evento de Google Calendar eliminado.');

            default:
                return $response->setError()->setMessage('No se puede eliminar este tipo de evento.');
        }
    }

    protected function parseEventId(string $rawId): array
    {
        if (str_starts_with($rawId, 'task_')) {
            return ['task', (int) str_replace('task_', '', $rawId)];
        }
        if (str_starts_with($rawId, 'reminder_')) {
            return ['reminder', (int) str_replace('reminder_', '', $rawId)];
        }
        if (str_starts_with($rawId, 'activity_')) {
            return ['activity', (int) str_replace('activity_', '', $rawId)];
        }
        if (str_starts_with($rawId, 'lead_')) {
            return ['lead', (int) str_replace('lead_', '', $rawId)];
        }
        if (str_starts_with($rawId, 'gcal_')) {
            return ['gcal', $rawId];
        }
        return ['unknown', $rawId];
    }
}

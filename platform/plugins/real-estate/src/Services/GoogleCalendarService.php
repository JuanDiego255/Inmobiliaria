<?php

namespace Botble\RealEstate\Services;

use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event as GoogleEvent;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    protected ?GoogleClient $client = null;

    public function isConfigured(): bool
    {
        return ! empty(setting('crm_gcal_client_id'))
            && ! empty(setting('crm_gcal_client_secret'))
            && ! empty(setting('crm_gcal_refresh_token'));
    }

    public function getClient(): GoogleClient
    {
        if ($this->client) {
            return $this->client;
        }

        $this->client = new GoogleClient();
        $this->client->setClientId(setting('crm_gcal_client_id'));
        $this->client->setClientSecret(setting('crm_gcal_client_secret'));
        $this->client->setRedirectUri($this->getRedirectUri());
        $this->client->addScope(GoogleCalendar::CALENDAR_EVENTS);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        $refreshToken = setting('crm_gcal_refresh_token');
        if ($refreshToken) {
            $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
        }

        return $this->client;
    }

    public function getRedirectUri(): string
    {
        return url('/') . '/auth/gcal';
    }

    public function getAuthUrl(): string
    {
        return $this->getClient()->createAuthUrl();
    }

    public function handleCallback(string $code): bool
    {
        try {
            $client = $this->getClient();
            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                Log::error('Google Calendar OAuth error', $token);
                return false;
            }

            if (! empty($token['refresh_token'])) {
                \Botble\Setting\Facades\Setting::set('crm_gcal_refresh_token', $token['refresh_token']);
                \Botble\Setting\Facades\Setting::save();
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Google Calendar callback failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function getCalendarId(): string
    {
        return setting('crm_gcal_calendar_id') ?: 'primary';
    }

    protected function getTimezone(): string
    {
        return config('app.timezone', 'America/Costa_Rica');
    }

    // ---- Create Events ----

    public function createEventFromTask($task): ?string
    {
        if (! $this->isConfigured() || ! setting('crm_gcal_sync_tasks')) {
            return null;
        }

        try {
            $service = new GoogleCalendar($this->getClient());

            $event = new GoogleEvent();
            $event->setSummary($task->title);
            $event->setDescription($this->buildTaskDescription($task));

            if ($task->due_date) {
                $dueDate = \Carbon\Carbon::parse($task->due_date);
                $start = new EventDateTime();
                $start->setDate($dueDate->format('Y-m-d'));
                $end = new EventDateTime();
                $end->setDate($dueDate->format('Y-m-d'));
                $event->setStart($start);
                $event->setEnd($end);
            } else {
                $start = new EventDateTime();
                $start->setDate(now()->format('Y-m-d'));
                $end = new EventDateTime();
                $end->setDate(now()->format('Y-m-d'));
                $event->setStart($start);
                $event->setEnd($end);
            }

            $created = $service->events->insert($this->getCalendarId(), $event);
            $eventId = $created->getId();

            $task->update(['google_event_id' => $eventId]);

            return $eventId;
        } catch (\Exception $e) {
            Log::error('Google Calendar create task event failed: ' . $e->getMessage());
            return null;
        }
    }

    public function createEventFromReminder($reminder): ?string
    {
        if (! $this->isConfigured() || ! setting('crm_gcal_sync_reminders')) {
            return null;
        }

        try {
            $service = new GoogleCalendar($this->getClient());

            $event = new GoogleEvent();
            $event->setSummary($reminder->title);
            $event->setDescription($this->buildReminderDescription($reminder));

            $remindAt = \Carbon\Carbon::parse($reminder->remind_at);

            $start = new EventDateTime();
            $start->setDateTime($remindAt->toRfc3339String());
            $start->setTimeZone($this->getTimezone());
            $end = new EventDateTime();
            $end->setDateTime($remindAt->copy()->addMinutes(30)->toRfc3339String());
            $end->setTimeZone($this->getTimezone());

            $event->setStart($start);
            $event->setEnd($end);

            $created = $service->events->insert($this->getCalendarId(), $event);
            $eventId = $created->getId();

            $reminder->update(['google_event_id' => $eventId]);

            return $eventId;
        } catch (\Exception $e) {
            Log::error('Google Calendar create reminder event failed: ' . $e->getMessage());
            return null;
        }
    }

    // ---- Update Events ----

    public function updateEvent(string $googleEventId, array $data): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $service = new GoogleCalendar($this->getClient());
            $calendarId = $this->getCalendarId();

            $event = $service->events->get($calendarId, $googleEventId);

            if (isset($data['title'])) {
                $event->setSummary($data['title']);
            }

            if (isset($data['description'])) {
                $event->setDescription($data['description']);
            }

            if (isset($data['start'])) {
                $start = new EventDateTime();
                if (strlen($data['start']) <= 10) {
                    $start->setDate($data['start']);
                } else {
                    $start->setDateTime(\Carbon\Carbon::parse($data['start'])->toRfc3339String());
                    $start->setTimeZone($this->getTimezone());
                }
                $event->setStart($start);
            }

            if (isset($data['end'])) {
                $end = new EventDateTime();
                if (strlen($data['end']) <= 10) {
                    $end->setDate($data['end']);
                } else {
                    $end->setDateTime(\Carbon\Carbon::parse($data['end'])->toRfc3339String());
                    $end->setTimeZone($this->getTimezone());
                }
                $event->setEnd($end);
            }

            $service->events->update($calendarId, $googleEventId, $event);

            return true;
        } catch (\Exception $e) {
            Log::error('Google Calendar update event failed: ' . $e->getMessage());
            return false;
        }
    }

    // ---- Delete Events ----

    public function deleteEvent(string $googleEventId): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $service = new GoogleCalendar($this->getClient());
            $service->events->delete($this->getCalendarId(), $googleEventId);
            return true;
        } catch (\Exception $e) {
            Log::error('Google Calendar delete event failed: ' . $e->getMessage());
            return false;
        }
    }

    // ---- List Events from Google Calendar ----

    public function listEvents(string $timeMin, string $timeMax): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $service = new GoogleCalendar($this->getClient());

            $params = [
                'timeMin' => \Carbon\Carbon::parse($timeMin)->toRfc3339String(),
                'timeMax' => \Carbon\Carbon::parse($timeMax)->toRfc3339String(),
                'singleEvents' => true,
                'orderBy' => 'startTime',
                'maxResults' => 250,
            ];

            $results = $service->events->listEvents($this->getCalendarId(), $params);
            $events = [];

            foreach ($results->getItems() as $event) {
                $start = $event->getStart();
                $end = $event->getEnd();

                $events[] = [
                    'id' => 'gcal_' . $event->getId(),
                    'google_event_id' => $event->getId(),
                    'title' => $event->getSummary() ?: '(Sin título)',
                    'start' => $start->getDateTime() ?: $start->getDate(),
                    'end' => $end->getDateTime() ?: $end->getDate(),
                    'allDay' => ! $start->getDateTime(),
                    'source' => 'google',
                    'color' => '#4285f4',
                    'textColor' => '#fff',
                    'editable' => true,
                    'url' => $event->getHtmlLink(),
                ];
            }

            return $events;
        } catch (\Exception $e) {
            Log::error('Google Calendar list events failed: ' . $e->getMessage());
            return [];
        }
    }

    // ---- Revoke ----

    public function revokeAccess(): bool
    {
        try {
            $this->getClient()->revokeToken();
        } catch (\Exception $e) {
            // ignore
        }

        \Botble\Setting\Facades\Setting::set('crm_gcal_refresh_token', '');
        \Botble\Setting\Facades\Setting::save();

        return true;
    }

    // ---- Description Builders ----

    protected function buildTaskDescription($task): string
    {
        $lines = ['Tarea del CRM'];
        if ($task->description) {
            $lines[] = '';
            $lines[] = $task->description;
        }
        if ($task->priority) {
            $lines[] = '';
            $lines[] = 'Prioridad: ' . ucfirst($task->priority);
        }
        if ($task->lead) {
            $lines[] = 'Lead: ' . ($task->lead->name ?? '');
        }
        $lines[] = '';
        $lines[] = 'Estado: ' . ucfirst($task->status ?? 'pending');

        return implode("\n", $lines);
    }

    protected function buildReminderDescription($reminder): string
    {
        $lines = ['Recordatorio del CRM'];
        if ($reminder->lead) {
            $lines[] = 'Lead: ' . ($reminder->lead->name ?? '');
        }
        return implode("\n", $lines);
    }
}

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
        return url('/') . '/' . \Botble\Base\Facades\BaseHelper::getAdminPrefix() . '/real-estate/crm/settings/google-calendar/callback';
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

    public function createEventFromTask($task): ?string
    {
        if (! $this->isConfigured() || ! setting('crm_gcal_sync_tasks')) {
            return null;
        }

        try {
            $service = new GoogleCalendar($this->getClient());
            $calendarId = setting('crm_gcal_calendar_id') ?: 'primary';

            $event = new GoogleEvent();
            $event->setSummary('📋 ' . $task->title);
            $event->setDescription($this->buildTaskDescription($task));

            if ($task->due_date) {
                $dueDate = \Carbon\Carbon::parse($task->due_date);

                if (strlen($task->due_date) <= 10) {
                    $start = new EventDateTime();
                    $start->setDate($dueDate->format('Y-m-d'));
                    $end = new EventDateTime();
                    $end->setDate($dueDate->format('Y-m-d'));
                } else {
                    $start = new EventDateTime();
                    $start->setDateTime($dueDate->toRfc3339String());
                    $start->setTimeZone(config('app.timezone', 'America/Costa_Rica'));
                    $end = new EventDateTime();
                    $end->setDateTime($dueDate->addHour()->toRfc3339String());
                    $end->setTimeZone(config('app.timezone', 'America/Costa_Rica'));
                }

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

            $created = $service->events->insert($calendarId, $event);

            return $created->getId();
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
            $calendarId = setting('crm_gcal_calendar_id') ?: 'primary';

            $event = new GoogleEvent();
            $event->setSummary('🔔 ' . $reminder->title);
            $event->setDescription($this->buildReminderDescription($reminder));

            $remindAt = \Carbon\Carbon::parse($reminder->remind_at);

            $start = new EventDateTime();
            $start->setDateTime($remindAt->toRfc3339String());
            $start->setTimeZone(config('app.timezone', 'America/Costa_Rica'));
            $end = new EventDateTime();
            $end->setDateTime($remindAt->addMinutes(30)->toRfc3339String());
            $end->setTimeZone(config('app.timezone', 'America/Costa_Rica'));

            $event->setStart($start);
            $event->setEnd($end);

            $created = $service->events->insert($calendarId, $event);

            return $created->getId();
        } catch (\Exception $e) {
            Log::error('Google Calendar create reminder event failed: ' . $e->getMessage());
            return null;
        }
    }

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

    protected function buildTaskDescription($task): string
    {
        $lines = [];
        $lines[] = 'Tarea del CRM';
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
        $lines = [];
        $lines[] = 'Recordatorio del CRM';
        if ($reminder->lead) {
            $lines[] = 'Lead: ' . ($reminder->lead->name ?? '');
        }
        return implode("\n", $lines);
    }
}

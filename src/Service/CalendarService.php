<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\CalendarProvider;
use App\Model\Entity\CalendarSource;
use App\Model\Entity\User;
use Cake\Datasource\ModelAwareTrait;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;
use DateTimeZone;
use Google\Client as GoogleClient;
use Google\Exception as GoogleException;
use Google\Service\Calendar;
use Google\Service\Calendar\Event as GoogleEvent;

/**
 * Provides Calendar syncing.
 *
 * Longer term this should become abstract
 * from vendor details, but for now it is somewhat coupled to
 * google calendar as I don't have enough context on what
 * the right abstractions are.
 */
class CalendarService
{
    use ModelAwareTrait;

    /**
     * @var \Google\Client $client
     */
    private $client;

    /**
     * @var \App\Model\Table\CalendarSourcesTable
     */
    private $CalendarSources;

    /**
     * @var \App\Model\Table\CalendarProvidersTable
     */
    private $CalendarProviders;

    /**
     * @var \App\Model\Table\CalendarItemsTable
     */
    private $CalendarItems;

    public function __construct(GoogleClient $client)
    {
        $this->client = $client;
    }

    /**
     * Set the access token from a provider.
     *
     * This method will also check the token expiry and if the token
     * is close to being expired, a refresh token request will be made
     *
     * @param \App\Model\Entity\CalendarProvider $provider
     */
    public function setAccessToken(CalendarProvider $provider): void
    {
        $this->loadModel('CalendarProviders');
        $this->client->setAccessToken($provider->access_token);

        // If the token would expire soon, update it.
        if (time() > $provider->token_expiry->getTimestamp() - 120) {
            $this->client->fetchAccessTokenWithRefreshToken($provider->refresh_token);
            $token = $this->client->getAccessToken();
            $provider->access_token = $token['access_token'];
            $provider->token_expiry = FrozenTime::parse("+{$token['expires_in']} seconds");
            $this->CalendarProviders->save($provider);
        }
    }

    /**
     * Get a list of calendars in the user's account.
     *
     * This is used to build the list of calendars that the user can
     * add to their task views.
     *
     * @param \App\Model\Entity\CalendarSource[] $linked Existing calendar links for a provider.
     * @return \App\Model\Entity\CalendarSource[]
     */
    public function listUnlinkedCalendars(array $linked)
    {
        $calendar = new Calendar($this->client);
        try {
            $results = $calendar->calendarList->listCalendarList();
        } catch (GoogleException $e) {
            throw new BadRequestException('Could not fetch calendars.', $e);
        }
        $linkedIds = array_map(function ($item) {
            return $item->provider_id;
        }, $linked);

        $out = [];
        foreach ($results as $record) {
            if (in_array($record->id, $linkedIds, true)) {
                continue;
            }
            $out[] = new CalendarSource([
                'name' => $record->summary,
                'provider_id' => $record->id,
                'color' => 1,
            ]);
        }
        return $out;
    }

    public function syncEvents(User $user, CalendarSource $source)
    {
        $this->loadModel('CalendarSources');
        $this->loadModel('CalendarItems');

        $calendar = new Calendar($this->client);

        $time = new FrozenTime('-1 month');
        $options = $defaults = [
            'timeMin' => $time->format(FrozenTime::RFC3339),
        ];
        // Check if the user has a sync token for this source.
        // If so use it to continue syncing.
        if ($source->sync_token) {
            $options = ['syncToken' => $source->sync_token];
        }

        do {
            try {
                $results = $calendar->events->listEvents($source->provider_id, $options);
            } catch (\Exception $e) {
                if ($e->getCode() == 410) {
                    // Start a full sync as our sync token was not good
                    $options = $defaults;
                    continue;
                } else {
                    throw $e;
                }
            }
            foreach ($results as $event) {
                $this->syncEvent($source, $event);
            }
            $pageToken = $results->getNextPageToken();
        } while ($pageToken !== null);

        // Save the nextSyncToken for our next sync.
        $source->sync_token = $results->getNextSyncToken();
        $this->CalendarSources->saveOrFail($source);
    }

    private function syncEvent(CalendarSource $source, GoogleEvent $event)
    {
        if ($event->status === 'cancelled') {
            // Remove existing local records for cancelled events.
            $this->CalendarItems->deleteAll([
                'calendar_source_id' => $source->id,
                'provider_id' => $event->id,
            ]);
            return;
        }

        $tz = new DateTimeZone(date_default_timezone_get());
        $start = $event->getStart();
        $end = $event->getEnd();

        $eventTz = $start->getTimeZone();
        $datetimes = [$start->getDate, $end->getDate(), $start->getDateTime(), $end->getDateTime()];
        foreach ($datetimes as $i => $value) {
            if ($value && $i < 2) {
                $date = FrozenDate::parse($value, $eventTz ?? $tz);
                $date = $date->setTimezone($tz);
                $datetimes[$i] = $date;
            } elseif ($value) {
                $time = FrozenTime::parse($value, $eventTz ?? $tz);
                $time = $time->setTimezone($tz);
                $datetimes[$i] = $time;
            }
        }

        $record = $this->CalendarItems->find()
            ->where([
                'calendar_source_id' => $source->id,
                'provider_id' => $event->id,
            ])->first();

        if (!$record) {
            $record = $this->CalendarItems->newEmptyEntity();
        }
        $record = $this->CalendarItems->patchEntity($record, [
            'calendar_source_id' => $source->id,
            'provider_id' => $event->id,
            'title' => $event->summary,
            'start_date' => $datetimes[0],
            'end_date' => $datetimes[1],
            'start_time' => $datetimes[2],
            'end_time' => $datetimes[3],
            'all_day' => $datetimes[0] !== null,
            'html_link' => $event->htmlLink,
        ]);
        $this->CalendarItems->saveOrFail($record);
    }
}

<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\CalendarProvider;
use App\Model\Entity\CalendarSource;
use Cake\Datasource\ModelAwareTrait;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\Utility\Text;
use DateTimeZone;
use Google\Client as GoogleClient;
use Google\Exception as GoogleException;
use Google\Service\Calendar;
use Google\Service\Calendar\Channel as GoogleChannel;
use Google\Service\Calendar\Event as GoogleEvent;
use Google\Service\Calendar\Events as GoogleEvents;
use RuntimeException;

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
     * @var \App\Model\Table\CalendarSubscriptionsTable
     */
    private $CalendarSubscriptions;

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
            if (!empty($token['expires_in'])) {
                $provider->token_expiry = FrozenTime::parse("+{$token['expires_in']} seconds");
            } else {
                $provider->token_expiry = FrozenTime::parse('+7200 seconds');
            }
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
            Log::warning("Calendar list failed. error={$e->getMessage()}");
            throw new BadRequestException('Could not fetch calendars.', null, $e);
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

    public function getSourceForSubscription(string $identifier, string $verifier): CalendarSource
    {
        $this->loadModel('CalendarSources');
        $source = $this->CalendarSources->find()
            ->innerJoinWith('CalendarSubscriptions')
            ->contain('CalendarProviders')
            ->where([
                'CalendarSubscriptions.identifier' => $identifier,
                'CalendarSubscriptions.verifier' => $verifier,
            ])
            ->firstOrFail();

        /** @var \App\Model\Entity\CalendarSource */
        return $source;
    }

    /**
     * Create a watch subscription in google for a calendar.
     *
     * @see https://developers.google.com/calendar/api/guides/push
     */
    public function createSubscription(CalendarSource $source)
    {
        $this->loadModel('CalendarSubscriptions');

        $sub = $this->CalendarSubscriptions->newEmptyEntity();
        $sub->identifier = Text::uuid();
        $sub->verifier = Text::uuid();
        $sub->calendar_source_id = $source->id;
        // Save to the local database first, as we can get a notification from
        // google before the watch request completes.
        $this->CalendarSubscriptions->saveOrFail($sub);

        $calendar = new Calendar($this->client);
        $channel = new GoogleChannel();
        $channel->setId($sub->identifier);
        $channel->setAddress(Router::url(['_name' => 'googlenotification:update', '_full' => true]));
        $channel->setToken($sub->channel_token);
        $channel->setType('web_hook');

        try {
            $result = $calendar->events->watch($source->provider_id, $channel);
            $sub->expires_at = $result->getExpiration() / 1000;
            $this->CalendarSubscriptions->saveOrFail($sub);

            Log::info("Calendar subscription created. source={$source->id}");
        } catch (GoogleException $e) {
            $this->CalendarSubscriptions->delete($sub);
            Log::warning("Calendar subscription failed. error={$e->getMessage()}");
            throw new RuntimeException("Could not create subscription for source id={$source->id}", 0, $e);
        }

        return $sub;
    }

    /**
     * @see https://developers.google.com/calendar/v3/reference/channels/stop
     */
    public function cancelSubscriptions(CalendarSource $source)
    {
        $this->loadModel('CalendarSubscriptions');

        $subs = $this->CalendarSubscriptions
            ->find()
            ->where(['CalendarSubscriptions.calendar_source_id' => $source->id])
            ->all();
        $calendar = new Calendar($this->client);
        foreach ($subs as $sub) {
            $channel = new GoogleChannel();
            $channel->setId($sub->identifier);
            try {
                $calendar->channels->stop($channel);
                $this->CalendarSubscriptions->delete($sub);
            } catch (GoogleException $e) {
                Log::warning("Could not stop calendar subscription error={$e->getMessage()}");
            }
        }
    }

    /**
     * Sync events from google.
     *
     * @see https://developers.google.com/calendar/api/guides/sync
     */
    public function syncEvents(CalendarSource $source)
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

        try {
            $this->CalendarItems->getConnection()->transactional(
                function () use ($calendar, $defaults, $options, $source, $time) {
                    $pageToken = null;

                    do {
                        if ($pageToken !== null) {
                            $options['pageToken'] = $pageToken;
                            unset($options['timeMin']);
                        }

                        $results = $calendar->events->listEvents($source->provider_id, $options);
                        $instanceOpts = [
                            'timeMin' => $time->format(FrozenTime::RFC3339),
                            'timeMax' => $time->modify('+3 months')->format(FrozenTime::RFC3339),
                        ];
                        assert($results instanceof GoogleEvents);
                        foreach ($results as $event) {
                            $instances = [$event];
                            if (!empty($event->getRecurrence())) {
                                $instances = $calendar->events->instances(
                                    $source->provider_id,
                                    $event->id,
                                    $instanceOpts
                                );
                            }
                            foreach ($results as $event) {
                                $instances = [$event];
                                if (!empty($event->getRecurrence())) {
                                    $instances = $calendar->events->instances(
                                        $source->provider_id,
                                        $event->id,
                                        $instanceOpts
                                    );
                                }
                                foreach ($instances as $instance) {
                                    $this->syncEvent($source, $instance);
                                }
                            }
                        }
                        $pageToken = $results->getNextPageToken();
                    } while ($pageToken !== null);

                    // Save the nextSyncToken for our next sync.
                    $source->sync_token = $results->getNextSyncToken();
                    $source->last_sync = FrozenTime::now();
                    $this->CalendarSources->saveOrFail($source);

                    Log::info("Calendar sync complete. source={$source->id}");
                }
            );
        } catch (GoogleException $e) {
            $errorCode = $e->getCode();
            if ($errorCode == 410) {
                // Start a full sync as our sync token was not good
                $options = $defaults;
            } elseif ($errorCode == 403) {
                // Permission denied error, likely a rate limit
                Log::info('Calendar sync failed, rate limit hit. ' . $e->getMessage());
            } else {
                \Sentry\captureException($e);
                Log::info('Calendar sync failed. ' . $e->getMessage());
            }
        }
    }

    private function syncEvent(CalendarSource $source, GoogleEvent $event)
    {
        if ($event->status === 'cancelled' || $this->declinedAsAttendee($source, $event)) {
            Log::info("Remove cancelled event {$event->id}");
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
        $datetimes = [$start->getDate(), $end->getDate(), $start->getDateTime(), $end->getDateTime()];
        foreach ($datetimes as $i => $value) {
            if ($value && $i < 2) {
                // Dates don't have a timezone
                $date = FrozenDate::parse($value);
                $datetimes[$i] = $date;
            } elseif ($value) {
                $time = FrozenTime::parse($value, $eventTz ?? $tz);
                $time = $time->setTimezone($tz);
                $datetimes[$i] = $time;
            }
        }
        assert(count($datetimes) == 4, 'Should have 4 datetime values');

        /** @var \Cake\Datasource\EntityInterface $record */
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

    private function declinedAsAttendee(CalendarSource $source, GoogleEvent $event): bool
    {
        $attendees = $event->getAttendees();
        if (empty($attendees)) {
            return false;
        }
        foreach ($attendees as $attendee) {
            if ($attendee->getSelf() && $attendee->getResponseStatus() == 'declined') {
                return true;
            }
        }

        return false;
    }
}

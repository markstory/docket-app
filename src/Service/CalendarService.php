<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\CalendarProvider;
use App\Model\Entity\CalendarSource;
use App\Model\Table\CalendarItemsTable;
use App\Model\Table\CalendarProvidersTable;
use App\Model\Table\CalendarSourcesTable;
use App\Model\Table\CalendarSubscriptionsTable;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
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
use function Cake\Collection\collection;
use function Sentry\captureException;

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
    use LocatorAwareTrait;

    /**
     * @var \Google\Client $client
     */
    private GoogleClient $client;

    /**
     * @var \App\Model\Table\CalendarSourcesTable
     */
    private CalendarSourcesTable $CalendarSources;

    /**
     * @var \App\Model\Table\CalendarSubscriptionsTable
     */
    private CalendarSubscriptionsTable $CalendarSubscriptions;

    /**
     * @var \App\Model\Table\CalendarProvidersTable
     */
    private CalendarProvidersTable $CalendarProviders;

    /**
     * @var \App\Model\Table\CalendarItemsTable
     */
    private CalendarItemsTable $CalendarItems;

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
        $this->CalendarProviders = $this->fetchTable('CalendarProviders');
        $this->client->setAccessToken($provider->access_token);

        // If the token would expire soon, update it.
        if (time() > $provider->token_expiry->getTimestamp() - 120) {
            $this->client->fetchAccessTokenWithRefreshToken($provider->refresh_token);
            $token = $this->client->getAccessToken();
            $provider->access_token = $token['access_token'];
            if (!empty($token['expires_in'])) {
                $provider->token_expiry = DateTime::parse("+{$token['expires_in']} seconds");
            } else {
                $provider->token_expiry = DateTime::parse('+7200 seconds');
            }
            $this->CalendarProviders->save($provider);
        }
    }

    /**
     * Sync sources from the remote calendar service.
     */
    public function syncSources(CalendarProvider $provider): CalendarProvider
    {
        $this->client->setAccessToken($provider->access_token);
        $calendar = new Calendar($this->client);
        try {
            $results = $calendar->calendarList->listCalendarList();
        } catch (GoogleException $e) {
            Log::warning("Calendar list failed. error={$e->getMessage()}");
            throw new BadRequestException('Could not fetch calendars.', null, $e);
        }
        $existing = collection($provider->calendar_sources)->indexBy('provider_id')->toArray();

        $this->CalendarProviders = $this->fetchTable('CalendarProviders');
        $this->CalendarSources = $this->fetchTable('CalendarSources');

        $this->CalendarProviders->getConnection()->transactional(
            function () use ($results, $existing, $provider): void {
                /** @var array<\App\Model\Entity\CalendarSource> $newSources */
                $newSources = [];
                foreach ($results as $record) {
                    // Update or create
                    if (isset($existing[$record->id])) {
                        /** @var \App\Model\Entity\CalendarSource $source */
                        $source = $existing[$record->id];
                        $source->name = $record->summary;
                        $this->CalendarSources->saveOrFail($source);

                        // Remove from existing records so that remainder can be deleted.
                        unset($existing[$record->id]);
                        $newSources[] = $source;
                    } else {
                        /** @var \App\Model\Entity\CalendarSource $source */
                        $source = $this->CalendarSources->newEntity([
                            'calendar_provider_id' => $provider->id,
                            'name' => $record->summary,
                            'provider_id' => $record->id,
                            'color' => 1,
                            'synced' => false,
                        ]);
                        $this->CalendarSources->saveOrFail($source);
                        $newSources[] = $source;
                    }
                }
                // Any existing sources that are no longer present in the remote
                // must have been deleted there.
                if (!empty($existing)) {
                    $ids = collection($existing)->extract('id')->toList();
                    $this->CalendarSources->deleteAll(['id IN' => $ids]);
                }

                $provider->calendar_sources = $newSources;
            }
        );

        return $provider;
    }

    /**
     * Get a list of calendars in the user's account.
     *
     * This is used to build the list of calendars that the user can
     * add to their task views.
     *
     * @param array<\App\Model\Entity\CalendarSource> $linked Existing calendar links for a provider.
     * @return array<\App\Model\Entity\CalendarSource>
     */
    public function listUnlinkedCalendars(array $linked): array
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

    /**
     * Check if a read operation can be performed with the credentials
     */
    public function isAuthBroken(): bool
    {
        $calendar = new Calendar($this->client);
        try {
            $calendar->calendarList->listCalendarList();

            return false;
        } catch (GoogleException $e) {
            return true;
        }
    }

    public function getSourceForSubscription(string $identifier, string $verifier): CalendarSource
    {
        $this->CalendarSources = $this->fetchTable('CalendarSources');
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
        $this->CalendarSubscriptions = $this->fetchTable('CalendarSubscriptions');

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
        $channel->setType('webhook');

        try {
            $opts = ['eventTypes' => ['default', 'focusTime', 'outOfOffice']];
            $result = $calendar->events->watch($source->provider_id, $channel, $opts);
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
    public function cancelSubscriptions(CalendarSource $source): void
    {
        $this->CalendarSubscriptions = $this->fetchTable('CalendarSubscriptions');

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
    public function syncEvents(CalendarSource $source): void
    {
        $this->CalendarSources = $this->fetchTable('CalendarSources');
        $this->CalendarItems = $this->fetchTable('CalendarItems');

        $calendar = new Calendar($this->client);

        $time = new DateTime('-1 month');
        $options = $defaults = [
            'timeMin' => $time->format(DateTime::RFC3339),
            'eventTypes' => ['default', 'focusTime', 'outOfOffice'],
        ];
        // Check if the user has a sync token for this source.
        // If so use it to continue syncing.
        if ($source->sync_token) {
            $options = ['syncToken' => $source->sync_token];
        }

        try {
            $this->CalendarItems->getConnection()->transactional(
                function () use ($calendar, $defaults, $options, $source, $time): void {
                    $pageToken = null;

                    do {
                        if ($pageToken !== null) {
                            $options['pageToken'] = $pageToken;
                            unset($options['timeMin']);
                        }

                        $results = null;
                        try {
                            $results = $calendar->events->listEvents($source->provider_id, $options);
                        } catch (GoogleException $e) {
                            if ($e->getCode() == 410) {
                                // Start a full sync as our sync token was not good
                                $options = $defaults;
                                continue;
                            }
                            throw $e;
                        }
                        $instanceOpts = [
                            'timeMin' => $time->format(DateTime::RFC3339),
                            'timeMax' => $time->modify('+3 months')->format(DateTime::RFC3339),
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
                    $source->last_sync = DateTime::now();
                    $this->CalendarSources->saveOrFail($source);

                    Log::info("Calendar sync complete. source={$source->id}");
                }
            );
        } catch (GoogleException $e) {
            $errorCode = $e->getCode();
            if ($errorCode == 403) {
                // Permission denied error, likely a rate limit
                Log::info('Calendar sync failed, rate limit hit. ' . $e->getMessage());
            } else {
                captureException($e);
                Log::info('Calendar sync failed. ' . $e->getMessage());
            }
        }
    }

    private function syncEvent(CalendarSource $source, GoogleEvent $event): void
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
                $date = Date::parse($value);
                $datetimes[$i] = $date;
            } elseif ($value) {
                $time = DateTime::parse($value, $eventTz ?? $tz);
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

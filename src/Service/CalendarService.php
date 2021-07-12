<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\CalendarProvider;
use App\Model\Entity\CalendarSource;
use App\Model\Entity\User;
use Cake\Datasource\ModelAwareTrait;
use Cake\Http\Exception\BadRequestException;
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
        $options = [
            'timeMin' => $time->format(FrozenTime::RFC3339),
        ];
        // Check if the user has a sync token for this source.
        // If so use it to continue syncing.
        if ($source->sync_token) {
            $options['syncToken'] = $source->sync_token;
        }

        do {
            // Fetch events one page at a time.
            // A 410 means we need to start over again.
            try {
                $results = $calendar->events->listEvents($source->provider_id, $options);
            } catch (\Exception $e) {
                debug($results);
                unset($options['syncToken']);
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
        $tz = new DateTimeZone(date_default_timezone_get());
        $start = $event->getStart()->getDateTime();
        if ($start) {
            $start = new FrozenTime($start, $tz);
        }
        $end = $event->getEnd()->getDateTime();
        if ($end) {
            $end = new FrozenTime($end, $tz);
        }

        if ($start === null && $end === null) {
            return;
        }

        $item = $this->CalendarItems->newEntity([
            'calendar_source_id' => $source->id,
            'provider_id' => $event->id,
            'title' => $event->summary,
            'start_time' => $start,
            'end_time' => $end,
            'html_link' => $event->htmlLink,
        ]);
        $this->CalendarItems->saveOrFail($item);
    }
}

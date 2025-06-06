<?php
declare(strict_types=1);

namespace App\Test\TestCase;

use App\Model\Entity\ApiToken;
use App\Model\Entity\User;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Text;
use Calendar\Model\Entity\CalendarItem;
use Calendar\Model\Entity\CalendarProvider;
use Calendar\Model\Entity\CalendarSource;
use Calendar\Model\Entity\CalendarSubscription;
use Feeds\Model\Entity\Feed;
use Feeds\Model\Entity\FeedCategory;
use Feeds\Model\Entity\FeedItem;
use Feeds\Model\Entity\FeedItemUser;
use Feeds\Model\Entity\FeedSubscription;
use RuntimeException;
use Tasks\Model\Entity\Project;
use Tasks\Model\Entity\ProjectSection;
use Tasks\Model\Entity\Subtask;
use Tasks\Model\Entity\Task;
use VCR\VCR;

trait FactoryTrait
{
    use LocatorAwareTrait;

    protected function login($userId = 1)
    {
        $user = $this->fetchTable('Users')->get($userId);
        $this->session([
            'Auth' => $user,
        ]);
    }

    protected function loginApi($userId = 1)
    {
        $token = $this->makeApiToken($userId);
        $this->requestJson();
        $this->useApiToken($token->token);

        return $token;
    }

    protected function useApiToken(string $token)
    {
        $headers = $this->_request['headers'] ?? [];
        $headers['Authorization'] = 'bearer ' . $token;

        $this->configRequest(['headers' => $headers]);
    }

    /**
     * Send a request as a JSON api
     */
    protected function requestJson()
    {
        $headers = $this->_request['headers'] ?? [];
        $headers['Accept'] = 'application/json';

        $this->configRequest([
            'headers' => $headers,
        ]);
    }

    /**
     * Send a request as htmx
     */
    protected function useHtmx()
    {
        $this->configRequest([
            'headers' => ['Hx-Request' => 'true'],
        ]);
    }

    protected function makeApiToken($userId = 1, $props = []): ApiToken
    {
        $apiTokens = $this->fetchTable('ApiTokens');
        /** @var \App\Model\Entity\ApiToken $token */
        $token = $apiTokens->newEntity(array_merge([
            'last_used' => null,
        ], $props));
        $token->user_id = $userId;
        $token->token = Text::uuid();

        return $apiTokens->saveOrFail($token);
    }

    protected function getUser($email): User
    {
        $users = $this->fetchTable('Users');

        /** @var \App\Model\Entity\User $user */
        $user = $users->findByEmail($email)->firstOrFail();

        return $user;
    }

    protected function makeUser($email, $props = []): User
    {
        $users = $this->fetchTable('Users');
        /** @var \App\Model\Entity\User $user */
        $user = $users->newEntity(array_merge([
            'name' => 'Unknown',
            'email' => $email,
            'email_verified' => true,
            'password' => 'super sekret',
        ], $props));

        return $users->saveOrFail($user);
    }

    protected function makeProject($name, $userId = 1, $ranking = 0, $props = []): Project
    {
        $projects = $this->fetchTable('Tasks.Projects');
        $props = array_merge([
            'user_id' => $userId,
            'name' => $name,
            'color' => 1,
            'ranking' => $ranking,
        ], $props);
        /** @var \Tasks\Model\Entity\Project $project */
        $project = $projects->newEntity($props, ['accessibleFields' => ['*' => true]]);

        return $projects->saveOrFail($project);
    }

    protected function makeProjectSection($name, $projectId, $ranking = 0, $props = []): ProjectSection
    {
        $sections = $this->fetchTable('Tasks.ProjectSections');
        $props = array_merge([
            'project_id' => $projectId,
            'name' => $name,
            'ranking' => $ranking,
        ], $props);
        /** @var \Tasks\Model\Entity\ProjectSection $section */
        $section = $sections->newEntity($props, ['accessibleFields' => ['*' => true]]);

        return $sections->saveOrFail($section);
    }

    protected function makeFeedSubscription($categoryId, $feedId, $userId = 1, $props = []): FeedSubscription
    {
        $subscriptions = $this->fetchTable('Feeds.FeedSubscriptions');

        $props = array_merge([
            'user_id' => $userId,
            'feed_id' => $feedId,
            'feed_category_id' => $categoryId,
            'alias' => 'news site',
            'ranking' => 0,
        ], $props);
        /** @var \Feeds\Model\Entity\FeedCategory $feedCategory */
        $sub = $subscriptions->newEntity($props, ['accessibleFields' => ['*' => true]]);

        return $subscriptions->saveOrFail($sub);
    }

    protected function makeFeedCategory($name, $userId = 1, $props = []): FeedCategory
    {
        $categories = $this->fetchTable('Feeds.FeedCategories');

        $props = array_merge([
            'user_id' => $userId,
            'title' => $name,
            'ranking' => 0,
            'color' => 1,
        ], $props);
        /** @var \Feeds\Model\Entity\FeedCategory $feedCategory */
        $feedCategory = $categories->newEntity($props, ['accessibleFields' => ['*' => true]]);

        return $categories->saveOrFail($feedCategory);
    }

    protected function makeFeed(string $url, $props = []): Feed
    {
        $feeds = $this->fetchTable('Feeds.Feeds');
        $props = array_merge([
            'url' => $url,
            'refresh_interval' => 60 * 60 * 24,
        ], $props);
        /** @var \Feeds\Model\Entity\FeedCategory $feedCategory */
        $feed = $feeds->newEntity($props, ['accessibleFields' => ['*' => true]]);

        return $feeds->saveOrFail($feed);
    }

    protected function makeFeedItem(int $feedId, $props = []): FeedItem
    {
        $items = $this->fetchTable('Feeds.FeedItems');
        $props = array_merge([
            'feed_id' => $feedId,
            'guid' => md5((string)rand()),
            'url' => 'http://example.org/blog/hello-world',
            'title' => 'hello world',
            'summary' => 'first post!',
            'content' => '',
            'published_at' => DateTime::parse('-3 days'),
        ], $props);
        /** @var \Feeds\Model\Entity\FeedItem $item */
        $item = $items->newEntity($props, ['accessibleFields' => ['*' => true]]);

        return $items->saveOrFail($item);
    }

    protected function makeFeedItemUser(int $itemId, int $userId, $props = []): FeedItemUser
    {
        $items = $this->fetchTable('Feeds.FeedItemUsers');
        $props = array_merge([
            'feed_item_id' => $itemId,
            'user_id' => $userId,
            'read_at' => DateTime::now(),
        ], $props);
        /** @var \Feeds\Model\Entity\FeedItemUser $item */
        $item = $items->newEntity($props, ['accessibleFields' => ['*' => true]]);

        return $items->saveOrFail($item);
    }

    protected function makeTask($title, $projectId, $order, $props = []): Task
    {
        $tasks = $this->fetchTable('Tasks.Tasks');
        $props = array_merge([
            'project_id' => $projectId,
            'title' => $title,
            'day_order' => $order,
            'child_order' => $order,
        ], $props);
        /** @var \Tasks\Model\Entity\Task $task */
        $task = $tasks->newEntity($props, ['accessibleFields' => ['*' => true]]);

        return $tasks->saveOrFail($task);
    }

    protected function makeSubtask($title, $taskId, $ranking, $props = []): Subtask
    {
        $subtasks = $this->fetchTable('Tasks.Subtasks');
        /** @var \Tasks\Model\Entity\Subtask $subtask */
        $subtask = $subtasks->newEntity(array_merge([
            'task_id' => $taskId,
            'title' => $title,
            'ranking' => $ranking,
        ], $props));

        return $subtasks->saveOrFail($subtask);
    }

    protected function makeCalendarProvider($userId, $identifier, $props = []): CalendarProvider
    {
        $providers = $this->fetchTable('Calendar.CalendarProviders');
        /** @var \Calendar\Model\Entity\CalendarProvider $provider */
        $provider = $providers->newEntity(array_merge([
            'user_id' => $userId,
            'kind' => 'google',
            'identifier' => $identifier,
            'display_name' => $identifier,
            'access_token' => 'calendar-access-token',
            'refresh_token' => 'calendar-refresh-token',
            'token_expiry' => new DateTime('+1 hour'),
        ], $props));

        return $providers->saveOrFail($provider);
    }

    protected function makeCalendarSource($providerId, $name = 'primary', $props = []): CalendarSource
    {
        $sources = $this->fetchTable('Calendar.CalendarSources');
        /** @var \Calendar\Model\Entity\CalendarSource $source */
        $source = $sources->newEntity(array_merge([
            'calendar_provider_id' => $providerId,
            'provider_id' => $name,
            'name' => $name,
            'color' => 1,
        ], $props));

        return $sources->saveOrFail($source);
    }

    protected function makeCalendarItem($sourceId, $props = []): CalendarItem
    {
        $items = $this->fetchTable('Calendar.CalendarItems');
        $item = $items->newEntity(array_merge([
            'calendar_source_id' => $sourceId,
            'start_time' => DateTime::parse('-1 day -1 hours')->format('Y-m-d H:i:s'),
            'end_time' => DateTime::parse('-1 day')->format('Y-m-d H:i:s'),
        ], $props));

        return $items->saveOrFail($item);
    }

    protected function makeCalendarSubscription($sourceId, $identifier = null, $verifier = null, $expires = null): CalendarSubscription
    {
        $subs = $this->fetchTable('Calendar.CalendarSubscriptions');
        /** @var \Calendar\Model\Entity\CalendarSubscription $sub */
        $sub = $subs->newEntity([
            'calendar_source_id' => $sourceId,
            'identifier' => $identifier ?? Text::uuid(),
            'verifier' => $verifier ?? Text::uuid(),
            'expires_at' => $expires ?? strtotime('+1 week'),
        ]);
        $subs->saveOrFail($sub);

        return $sub;
    }

    public function readFeedFixture(string $fileName): string
    {
        $contents = file_get_contents(TESTS . "Fixture/feeds/$fileName");
        if (!$contents) {
            throw new RuntimeException("Could not read feed fixture $fileName");
        }

        return $contents;
    }

    /**
     * Load a VCR cassette for http response stubs.
     *
     * @param string $name The name of the fixture to load including extension.
     */
    protected function loadResponseMocks(string $name): void
    {
        VCR::turnOn();
        VCR::insertCassette($name);
    }

    /**
     * After hook that clears VCR cassettes that have been loaded.
     *
     * @after
     */
    protected function clearResponseMocks(): void
    {
        VCR::turnOff();
    }
}

<?php
declare(strict_types=1);

namespace App\Test\TestCase;

use App\Model\Entity\ApiToken;
use App\Model\Entity\CalendarItem;
use App\Model\Entity\CalendarProvider;
use App\Model\Entity\CalendarSource;
use App\Model\Entity\CalendarSubscription;
use App\Model\Entity\Project;
use App\Model\Entity\ProjectSection;
use App\Model\Entity\Subtask;
use App\Model\Entity\Task;
use App\Model\Entity\User;
use Cake\I18n\FrozenTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Text;
use DateTime;

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

    protected function useApiToken($token)
    {
        $headers = $this->_request['headers'] ?? [];
        $headers['Authorization'] = 'bearer ' . $token;

        $this->configRequest(['headers' => $headers]);
    }

    protected function requestJson()
    {
        $headers = $this->_request['headers'] ?? [];
        $headers['Accept'] = 'application/json';

        $this->configRequest([
            'headers' => $headers,
        ]);
    }

    protected function makeApiToken($userId = 1, $props = []): ApiToken
    {
        $apiTokens = $this->fetchTable('ApiTokens');
        $token = $apiTokens->newEntity(array_merge([
            'last_used' => null,
        ], $props));
        $token->user_id = $userId;
        $token->token = Text::uuid();

        return $apiTokens->saveOrFail($token);
    }

    protected function makeUser($email, $props = []): User
    {
        $users = $this->fetchTable('Users');
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
        $projects = $this->fetchTable('Projects');
        $props = array_merge([
            'user_id' => $userId,
            'name' => $name,
            'color' => 1,
            'ranking' => $ranking,
        ], $props);
        $project = $projects->newEntity($props, ['accessibleFields' => ['*' => true]]);

        return $projects->saveOrFail($project);
    }

    protected function makeProjectSection($name, $projectId, $ranking = 0, $props = []): ProjectSection
    {
        $sections = $this->fetchTable('ProjectSections');
        $props = array_merge([
            'project_id' => $projectId,
            'name' => $name,
            'ranking' => $ranking,
        ], $props);
        $section = $sections->newEntity($props, ['accessibleFields' => ['*' => true]]);

        return $sections->saveOrFail($section);
    }

    protected function makeTask($title, $projectId, $order, $props = []): Task
    {
        $tasks = $this->fetchTable('Tasks');
        $props = array_merge([
            'project_id' => $projectId,
            'title' => $title,
            'day_order' => $order,
            'child_order' => $order,
        ], $props);
        $task = $tasks->newEntity($props, ['accessibleFields' => ['*' => true]]);

        return $tasks->saveOrFail($task);
    }

    protected function makeSubtask($title, $taskId, $ranking, $props = []): Subtask
    {
        $subtasks = $this->fetchTable('Subtasks');
        $subtask = $subtasks->newEntity(array_merge([
            'task_id' => $taskId,
            'title' => $title,
            'ranking' => $ranking,
        ], $props));

        return $subtasks->saveOrFail($subtask);
    }

    protected function makeCalendarProvider($userId, $identifier, $props = []): CalendarProvider
    {
        $providers = $this->fetchTable('CalendarProviders');
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
        $sources = $this->fetchTable('CalendarSources');
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
        $items = $this->fetchTable('CalendarItems');
        $item = $items->newEntity(array_merge([
            'calendar_source_id' => $sourceId,
            'start_time' => FrozenTime::parse('-1 day -1 hours')->format('Y-m-d H:i:s'),
            'end_time' => FrozenTime::parse('-1 day')->format('Y-m-d H:i:s'),
        ], $props));

        return $items->saveOrFail($item);
    }

    protected function makeCalendarSubscription($sourceId, $identifier = null, $verifier = null, $expires = null): CalendarSubscription
    {
        $subs = $this->fetchTable('CalendarSubscriptions');
        $sub = $subs->newEntity([
            'calendar_source_id' => $sourceId,
            'identifier' => $identifier ?? Text::uuid(),
            'verifier' => $verifier ?? Text::uuid(),
            'expires_at' => $expires ?? strtotime('+1 week'),
        ]);

        return $subs->saveOrFail($sub);
    }
}

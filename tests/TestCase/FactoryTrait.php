<?php
declare(strict_types=1);

namespace App\Test\TestCase;

use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use DateTime;

trait FactoryTrait
{
    protected function login($userId = 1)
    {
        $user = TableRegistry::get('Users')->get($userId);
        $this->session([
            'Auth' => $user,
        ]);
    }

    protected function makeUser($email, $props = [])
    {
        $users = TableRegistry::get('Users');
        $user = $users->newEntity(array_merge([
            'name' => 'Unknown',
            'email' => $email,
            'email_verified' => true,
            'password' => 'super sekret',
        ], $props));

        return $users->saveOrFail($user);
    }

    protected function makeProject($name, $userId, $ranking = 0, $props = [])
    {
        $projects = TableRegistry::get('Projects');
        $props = array_merge([
            'user_id' => $userId,
            'name' => $name,
            'color' => 1,
            'ranking' => $ranking,
        ], $props);
        $project = $projects->newEntity($props, ['accessibleFields' => ['*' => true]]);

        return $projects->saveOrFail($project);
    }

    protected function makeProjectSection($name, $projectId, $ranking = 0, $props = [])
    {
        $sections = TableRegistry::get('ProjectSections');
        $props = array_merge([
            'project_id' => $projectId,
            'name' => $name,
            'ranking' => $ranking,
        ], $props);
        $section = $sections->newEntity($props, ['accessibleFields' => ['*' => true]]);

        return $sections->saveOrFail($section);
    }

    protected function makeTask($title, $projectId, $order, $props = [])
    {
        $tasks = TableRegistry::get('Tasks');
        $props = array_merge([
            'project_id' => $projectId,
            'title' => $title,
            'day_order' => $order,
            'child_order' => $order,
        ], $props);
        $task = $tasks->newEntity($props, ['accessibleFields' => ['*' => true]]);

        return $tasks->saveOrFail($task);
    }

    protected function makeSubtask($title, $taskId, $ranking, $props = [])
    {
        $subtasks = TableRegistry::get('Subtasks');
        $subtask = $subtasks->newEntity(array_merge([
            'task_id' => $taskId,
            'title' => $title,
            'ranking' => $ranking,
        ], $props));

        return $subtasks->saveOrFail($subtask);
    }

    protected function makeCalendarProvider($userId, $identifier, $props = [])
    {
        $providers = TableRegistry::get('CalendarProviders');
        $provider = $providers->newEntity(array_merge([
            'user_id' => $userId,
            'kind' => 'google',
            'identifier' => $identifier,
            'access_token' => 'calendar-access-token',
            'refresh_token' => 'calendar-refresh-token',
            'token_expiry' => new Datetime('+1 hour'),
        ], $props));

        return $providers->saveOrFail($provider);
    }

    protected function makeCalendarSource($providerId, $name = 'primary', $props = [])
    {
        $sources = TableRegistry::get('CalendarSources');
        $source = $sources->newEntity(array_merge([
            'calendar_provider_id' => $providerId,
            'provider_id' => $name,
            'name' => $name,
            'color' => 1,
        ], $props));

        return $sources->saveOrFail($source);
    }

    protected function makeCalendarItem($sourceId, $props = [])
    {
        $items = TableRegistry::get('CalendarItems');
        $item = $items->newEntity(array_merge([
            'calendar_source_id' => $sourceId,
            'start_time' => FrozenTime::parse('-1 day -1 hours')->format('Y-m-d H:i:s'),
            'end_time' => FrozenTime::parse('-1 day')->format('Y-m-d H:i:s'),
        ], $props));

        return $items->saveOrFail($item);
    }
}

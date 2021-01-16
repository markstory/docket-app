<?php
declare(strict_types=1);

namespace App\Test\Acceptance;

use Cake\ORM\TableRegistry;

class TaskCreateTest extends AcceptanceTestCase
{
    public function testTaskCreateFromToday()
    {
        $project = $this->makeProject('Work', 1);

        $client = $this->login();
        $client->get('/tasks/today');
        $client->waitFor('[data-testid="loggedin"]');

        // Open the add form
        $button = $client->getCrawler()->filter('.add-task button');
        $button->click();
        $client->waitFor('.task-quickform');

        $form = $client->getCrawler()->filter('.task-quickform')->form();
        $client->submit($form, [
            'title' => 'A new task',
        ]);
        debug($client->getPageSource());

        $tasks = TableRegistry::get('Tasks');
        $task = $tasks->find()->first();
        $this->assertNotEmpty($task);
        $this->assertEquals('A new task', $task->title);
    }
}

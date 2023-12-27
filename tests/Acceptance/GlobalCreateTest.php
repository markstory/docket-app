<?php
declare(strict_types=1);

namespace App\Test\Acceptance;

class GlobalCreateTest extends AcceptanceTestCase
{
    public function testKeyboardTriggerFromToday()
    {
        // TODO fix this
        $this->markTestIncomplete('keybindings for global actions are not complete');
        $project = $this->makeProject('Home', 1);

        $client = $this->login();
        $client->get('/tasks/today');
        $client->waitFor('[data-testid="loggedin"]');

        // Open modal, wait for modal.
        $client->getKeyboard()->sendKeys('c');
        $client->waitFor('[aria-modal="true"]');

        $crawler = $client->getCrawler();
        // Fill out title
        // TODO update this to use a better selector
        $crawler->filter('.task-quickform .smart-task-input input')
            ->sendKeys('task title');

        // Show notes
        $crawler->filter('[data-testid="add-notes"]')->click();
        $crawler->filter('[name="body"]')->sendKeys('Some notes');

        $crawler->filter('[data-testid="save-task"]')->click();

        $tasks = $this->fetchTable('Tasks');
        $task = $tasks->find()->firstOrFail();
        $this->assertEquals('task title', $task->title);
        $this->assertEquals($project->id, $task->project_id);
        $this->assertEquals('Some notes', $task->body);
    }

    public function testButtonTriggerFromUpcoming()
    {
        // TODO fix this
        $this->markTestIncomplete('keybindings for global actions are not complete');
        $project = $this->makeProject('Home', 1);

        $client = $this->login();
        $client->get('/tasks/upcoming');
        $client->waitFor('[data-testid="loggedin"]');

        // Open modal, wait for modal.
        $crawler = $client->getCrawler();
        $crawler->filter('[data-testid="global-task-add"]')->click();
        $client->waitFor('[aria-modal="true"]');

        // Fill out title
        // TODO update this to use a better selector
        $crawler->filter('.task-quickform .smart-task-input input')
            ->sendKeys('task title');

        // Show notes
        $crawler->filter('[data-testid="add-notes"]')->click();
        $crawler->filter('[name="body"]')->sendKeys('Some notes');

        $crawler->filter('[data-testid="save-task"]')->click();

        $tasks = $this->fetchTable('Tasks');
        $task = $tasks->find()->firstOrFail();
        $this->assertEquals('task title', $task->title);
        $this->assertEquals($project->id, $task->project_id);
        $this->assertEquals('Some notes', $task->body);
    }
}

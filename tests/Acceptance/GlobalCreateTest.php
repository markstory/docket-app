<?php
declare(strict_types=1);

namespace App\Test\Acceptance;

class GlobalCreateTest extends AcceptanceTestCase
{
    public function testKeyboardTriggerFromToday()
    {
        $project = $this->makeProject('Home', 1);

        $client = $this->login();
        $client->get('/tasks/today');
        $client->waitFor('[data-testid="loggedin"]');

        // Open modal, wait for modal.
        $client->getKeyboard()->sendKeys('c');
        $client->waitFor('.sheet-body');

        $crawler = $client->getCrawler();
        $crawler->filter('input[name="title"]')->sendKeys('task title');

        // Show notes
        $crawler->filter('.markdown-text-preview [role="button"]')->click();
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
        $project = $this->makeProject('Home', 1);

        $client = $this->login();
        $client->get('/tasks/upcoming');
        $client->waitFor('[data-testid="loggedin"]');

        // Open modal, wait for modal.
        $crawler = $client->getCrawler();
        $client->getKeyboard()->sendKeys('c');
        $client->waitFor('.sheet-body');

        // Fill out title
        $crawler->filter('input[name="title"]')->sendKeys('task title');
        $crawler->filter('[data-testid="save-task"]')->click();

        $tasks = $this->fetchTable('Tasks');
        $task = $tasks->find()->firstOrFail();
        $this->assertEquals('task title', $task->title);
        $this->assertEquals($project->id, $task->project_id);
    }
}

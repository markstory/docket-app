<?php
declare(strict_types=1);

namespace App\Test\Acceptance;

use Cake\I18n\Date;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Symfony\Component\Panther\Client;

class KeyboardNavigationTest extends AcceptanceTestCase
{
    protected function waitForTitleChange(Client $client, string $title)
    {
        $client->wait(20, 1000)->until(
            WebDriverExpectedCondition::not(
                WebDriverExpectedCondition::titleContains($title)
            )
        );
    }

    public function testUpcoming()
    {
        $this->makeProject('Home', 1);
        $client = $this->login();

        $client->get('/tasks/today');
        $client->waitFor('[data-testid="loggedin"]');
        $title = $client->getTitle();

        $client->getKeyboard()->sendKeys('u');

        $this->waitForTitleChange($client, $title);
        $this->assertStringContainsString('Upcoming Tasks', $client->getTitle());
    }

    public function testToday()
    {
        $this->makeProject('Home', 1);

        $client = $this->login();
        $client->get('/tasks/upcoming');
        $client->waitFor('[data-testid="loggedin"]');
        $title = $client->getTitle();

        $client->getKeyboard()->sendKeys('t');
        $this->waitForTitleChange($client, $title);
        $this->assertStringContainsString("Today's Tasks", $client->getTitle());
    }

    public function testTaskListGotoDetails()
    {
        $tomorrow = new Date('tomorrow');
        $project = $this->makeProject('Home', 1);
        $this->makeTask('Clean', $project->id, 0, ['due_on' => $tomorrow]);
        $this->makeTask('Laundry', $project->id, 1, ['due_on' => $tomorrow]);

        $client = $this->login();
        $client->get('/tasks/upcoming');
        $client->waitFor('[data-testid="loggedin"]');
        $title = $client->getTitle();

        // Move focus
        $client->getKeyboard()->sendKeys('j');
        $client->waitFor('.keyboard-focus');
        // Open details
        $client->getKeyboard()->sendKeys('o');

        $this->waitForTitleChange($client, $title);
        $this->assertStringContainsString('Clean', $client->getTitle());
    }

    public function testTaskListMarkComplete()
    {
        $tomorrow = new Date('tomorrow');
        $project = $this->makeProject('Home', 1);
        $task = $this->makeTask('Clean', $project->id, 0, ['due_on' => $tomorrow]);

        $client = $this->login();
        $client->get('/tasks/upcoming');
        $client->waitFor('[data-testid="loggedin"]');

        // Move focus
        $client->getKeyboard()->sendKeys('j');
        $client->waitFor('.keyboard-focus');
        // Mark done
        $client->getKeyboard()->sendKeys('x');

        $client->waitFor('.flash-message');
        $task = $this->getTableLocator()->get('Tasks')->get($task->id);
        $this->assertTrue($task->completed);
    }

    public function testGlobalCreate()
    {
        $this->makeProject('Home', 1);

        $client = $this->login();
        $client->get('/tasks/upcoming');
        $client->waitFor('[data-testid="loggedin"]');

        // Open modal, wait for modal.
        $client->getKeyboard()->sendKeys('c');
        $client->waitFor('.sheet-body');

        $this->assertNotEmpty($client->getCrawler()->filter('.task-add-contents'));
    }
}

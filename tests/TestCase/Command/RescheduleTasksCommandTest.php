<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Test\TestCase\FactoryTrait;
use Cake\Core\Configure;
use Cake\I18n\FrozenDate;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Command\RescheduleTasksCommand Test Case
 *
 * @uses \App\Command\RescheduleTasksCommand
 */
class RescheduleTasksCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use FactoryTrait;

    public array $fixtures = [
        'app.Users',
        'app.Projects',
        'app.Tasks',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test execute method
     *
     * @return void
     */
    public function testExecuteNoDebug(): void
    {
        Configure::write('debug', false);
        $this->exec('reschedule_tasks');
        $this->assertExitError();
    }

    /**
     * Test execute method
     *
     * @return void
     */
    public function testExecute(): void
    {
        Configure::write('debug', true);
        $project = $this->makeProject('Home', 1);
        $task = $this->makeTask('Do dishes', $project->id, 1, ['due_on' => new \Cake\I18n\Date('-1 day')]);

        $this->exec('reschedule_tasks');
        $this->assertExitSuccess();
        $this->assertOutputContains('Updated 1 tasks');

        $updated = $this->fetchTable('Tasks')->get($task->id);
        $this->assertTrue($task->due_on->lessThan($updated->due_on));
    }
}

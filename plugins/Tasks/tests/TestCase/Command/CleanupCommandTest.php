<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Test\TestCase\FactoryTrait;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * App\Command\CleanupCommand Test Case
 */
class CleanupCommandTest extends TestCase
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
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test execute method
     *
     * @return void
     */
    public function testExecute(): void
    {
        $expired = DateTime::parse('-15 days');
        $ok = DateTime::parse('-1 hour');

        $project = $this->makeProject('work', 1);
        $this->makeTask('first task', $project->id, 1, ['deleted_at' => $expired]);
        $this->makeTask('second task', $project->id, 2, ['deleted_at' => $expired]);
        $keepOne = $this->makeTask('deleted but later', $project->id, 3, ['deleted_at' => $ok]);
        $keepTwo = $this->makeTask('keeper task', $project->id, 4, ['deleted_at' => null]);

        $this->exec('cleanup');
        $this->assertExitSuccess();

        $tasks = $this->fetchTable('Tasks.Tasks');
        $this->assertNotEmpty($tasks->get($keepOne->id, deleted: true));
        $this->assertNotEmpty($tasks->get($keepTwo->id));
    }
}

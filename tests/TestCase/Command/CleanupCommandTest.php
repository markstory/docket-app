<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Test\TestCase\FactoryTrait;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Command\CleanupCommand Test Case
 *
 * @uses \App\Command\CleanupCommand
 */
class CleanupCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use FactoryTrait;

    public $fixtures = [
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
        $this->useCommandRunner();
    }

    /**
     * Test execute method
     *
     * @return void
     * @uses \App\Command\CleanupCommand::execute()
     */
    public function testExecute(): void
    {
        $expired = FrozenTime::parse('-15 days');
        $ok = FrozenTime::parse('-1 hour');

        $project = $this->makeProject('work', 1);
        $this->makeTask('first task', $project->id, 1, ['deleted_at' => $expired]);
        $this->makeTask('second task', $project->id, 2, ['deleted_at' => $expired]);
        $keepOne = $this->makeTask('deleted but later', $project->id, 3, ['deleted_at' => $ok]);
        $keepTwo = $this->makeTask('keeper task', $project->id, 4, ['deleted_at' => null]);

        $this->exec('cleanup');
        $this->assertExitSuccess();

        $tasks = $this->fetchTable('Tasks');
        $result = $tasks->find()->all();
        $this->assertNotEmpty($tasks->get($keepOne->id, ['deleted' => true]));
        $this->assertNotEmpty($tasks->get($keepTwo->id));
    }
}

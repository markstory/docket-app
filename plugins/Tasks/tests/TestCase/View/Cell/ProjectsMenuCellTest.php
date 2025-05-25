<?php
declare(strict_types=1);

namespace Tasks\Test\TestCase\View\Cell;

use App\Test\TestCase\FactoryTrait;
use Authorization\AuthorizationService;
use Authorization\Policy\OrmResolver;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Tasks\View\Cell\ProjectsMenuCell;

/**
 * Tasks\View\Cell\ProjectsMenuCell Test Case
 */
class ProjectsMenuCellTest extends TestCase
{
    use FactoryTrait;

    protected array $fixtures = ['app.Users', 'plugin.Tasks.Projects'];
    /**
     * Request mock
     *
     * @var \Cake\Http\ServerRequest|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $request;

    /**
     * Response mock
     *
     * @var \Cake\Http\Response|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $response;

    protected $user;

    /**
     * Test subject
     *
     * @var \Tasks\View\Cell\ProjectsMenuCell
     */
    protected $ProjectsMenu;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadPlugins(['Tasks']);

        $this->request = new ServerRequest(['url' => '/']);
        $this->response = new Response();
        $user = $this->getUser('mark@example.com');
        // TODO this is nasty.
        $user->setAuthorization(new AuthorizationService(new OrmResolver()));
        $this->user = $user;

        $this->ProjectsMenu = new ProjectsMenuCell(
            $this->request,
            $this->response,
            null,
            ['action' => 'display', 'args' => ['identity' => $this->user]],
        );
        $this->ProjectsMenu->viewBuilder()->setPlugin('Tasks');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->ProjectsMenu);

        parent::tearDown();
    }

    /**
     * Test display method
     *
     * @return void
     */
    public function testDisplay(): void
    {
        $one = $this->makeProject('home', 1, 2);
        $two = $this->makeProject('hobbies', 1, 2);
        $archived = $this->makeProject('archived', $this->user->id, 3, ['archived' => 1]);
        // Different user
        $other = $this->makeProject('work', 2, 1);

        $content = $this->ProjectsMenu->render('display');
        $this->assertStringNotContainsString($archived->name, $content);
        $this->assertStringNotContainsString($other->name, $content);
        $this->assertStringContainsString($one->name, $content);
        $this->assertStringContainsString($two->name, $content);
    }
}

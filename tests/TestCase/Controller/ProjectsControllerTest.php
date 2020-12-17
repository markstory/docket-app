<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ProjectsController;
use App\Test\TestCase\FactoryTrait;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\ProjectsController Test Case
 *
 * @uses \App\Controller\ProjectsController
 */
class ProjectsControllerTest extends TestCase
{
    use FactoryTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.Projects',
        'app.Users',
        'app.TodoItems',
        'app.TodoLabels',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->Projects = TableRegistry::get('Projects');
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView(): void
    {
        $home = $this->makeProject('Home', 1, 0, ['archived' => true]);
        $this->makeItem('first post', $home->id, 0);

        $this->login();
        $this->get("/projects/{$home->slug}");

        $this->assertResponseOk();
        $this->assertSame($home->id, $this->viewVariable('project')->id);
        $this->assertCount(1, $this->viewVariable('todoItems'));
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAddReserved(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/add", [
            'name' => 'add',
            'color' => '663366',
        ]);
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Type', 'application/json');

        $project = $this->Projects->find()->first();
        $this->assertEquals('add', $project->name);
        $this->assertNotEquals('add', $project->slug);
    }

    public function testEdit(): void
    {
        $home = $this->makeProject('Home', 1, 0, ['archived' => true]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$home->slug}/edit", [
            'name' => 'Home too',
            'color' => '999999',
        ]);
        $this->assertRedirect('/todos/upcoming');

        $project = $this->Projects->find()->where(['Projects.id' => $home->id])->firstOrFail();
        $this->assertEquals('Home too', $project->name);
        $this->assertEquals('999999', $project->color);
        $this->assertEquals(0, $project->ranking);
    }

    public function testEditPermission(): void
    {
        $home = $this->makeProject('Home', 2, 0, ['archived' => true]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$home->slug}/edit", [
            'name' => 'Home too',
            'color' => '999999',
        ]);
        $this->assertResponseCode(403);
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testAddAppendRanking(): void
    {
        $this->makeProject('Home', 1, 0, ['archived' => true]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/add", [
            'name' => 'second',
            'color' => '663366',
        ]);
        $this->assertResponseOk();

        $project = $this->Projects->find()->where(['Projects.slug' => 'second'])->firstOrFail();
        $this->assertEquals('second', $project->name);
        $this->assertEquals(1, $project->ranking);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $home = $this->makeProject('Home', 1, 0, ['archived' => true]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$home->slug}/delete");
        $this->assertRedirect("/todos/today");
        $this->assertFalse($this->Projects->exists(['slug' => $home->slug]));
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDeletePermission(): void
    {
        $home = $this->makeProject('Home', 2, 0, ['archived' => true]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$home->slug}/delete");
        $this->assertResponseCode(403);
        $this->assertTrue($this->Projects->exists(['slug' => $home->slug]));
    }

    public function testArchived()
    {
        $this->makeProject('Home', 1, 0, ['archived' => true]);
        $this->makeProject('Work', 1, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->get("/projects/archived");

        $this->assertResponseOk();
        $archived = $this->viewVariable('archived');
        $this->assertCount(1, $archived);
    }

    public function testArchive()
    {
        $home = $this->makeProject('Home', 1, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$home->slug}/archive");
        $this->assertRedirect('/todos/today');

        $result = $this->Projects->find()->where(['id' => $home->id])->first();
        $this->assertTrue($result->archived);
    }

    public function testArchivePermission()
    {
        $home = $this->makeProject('Home', 2, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$home->slug}/archive");
        $this->assertResponseCode(403);
    }

    public function testUnarchive()
    {
        $home = $this->makeProject('Home', 1, 0, ['archived' => true]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$home->slug}/unarchive");
        $this->assertRedirect('/todos/today');

        $result = $this->Projects->find()->where(['id' => $home->id])->first();
        $this->assertFalse($result->archived);
    }

    public function testUnArchivePermission()
    {
        $home = $this->makeProject('Home', 2, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$home->slug}/unarchive");
        $this->assertResponseCode(403);
    }

    public function testMovePermission()
    {
        $home = $this->makeProject('Home', 1, 0);
        $work = $this->makeProject('Work', 2, 3);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$work->slug}/move", [
            'ranking' => 0,
        ]);
        $this->assertResponseCode(403);
    }

    public function testMoveNoData()
    {
        $home = $this->makeProject('Home', 1, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->post("/projects/{$home->slug}/move");
        $this->assertRedirect('/todos/today');
        $this->assertFlashElement('flash/error');
    }

    public function testMoveDown()
    {
        $home = $this->makeProject('Home', 1, 0);
        $work = $this->makeProject('Work', 1, 3);
        $fun = $this->makeProject('Fun', 1, 6);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$home->slug}/move", [
            'ranking' => 1,
        ]);
        $this->assertRedirect('/todos/today');

        $results = $this->Projects->find()->orderAsc('ranking')->toArray();
        $expected = [$work->id, $home->id, $fun->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }

    public function testMoveUp()
    {
        $home = $this->makeProject('Home', 1, 0);
        $work = $this->makeProject('Work', 1, 3);
        $fun = $this->makeProject('Fun', 1, 6);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$fun->slug}/move", [
            'ranking' => 0,
        ]);
        $this->assertRedirect('/todos/today');

        $results = $this->Projects->find()->orderAsc('ranking')->toArray();
        $expected = [$fun->id, $home->id, $work->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }
}

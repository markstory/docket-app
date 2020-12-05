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
     * Test index method
     *
     * @return void
     */
    public function testIndex(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
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
        $project = $this->Projects->find()->first();
        $this->assertEquals('add', $project->name);
        $this->assertNotEquals('add', $project->slug);
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    public function testArchived()
    {
        $home = $this->makeProject('Home', 1, 0, ['archived' => true]);
        $work = $this->makeProject('Work', 1, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->get("/projects/{$home->slug}/archived");

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
        $this->assertRedirect('/projects');

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
        $this->assertRedirect('/projects');

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

    public function testReorderSuccess()
    {
        $home = $this->makeProject('Home', 1, 0);
        $work = $this->makeProject('Work', 1, 3);
        $fun = $this->makeProject('Fun', 1, 6);

        $this->login();
        $this->enableCsrfToken();
        $expected = [$fun->id, $work->id, $home->id];
        $this->post('/projects/reorder', [
            'projects' => $expected,
        ]);
        $this->assertRedirect('/projects');

        $results = $this->Projects->find()->orderAsc('ranking')->toArray();
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }

    public function testReorderOtherOwner()
    {
        $home = $this->makeProject('Home', 1, 0);
        $work = $this->makeProject('Work', 2, 3);

        $this->login();
        $this->enableCsrfToken();
        $expected = [$work->id, $home->id];
        $this->post('/projects/reorder', [
            'projects' => $expected,
        ]);
        $this->assertResponseCode(404);
    }
}

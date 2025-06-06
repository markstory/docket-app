<?php
declare(strict_types=1);

namespace Tasks\Test\TestCase\Controller;

use App\Test\TestCase\FactoryTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Tasks\Model\Table\ProjectsTable;

/**
 * App\Controller\ProjectsController Test Case
 */
class ProjectsControllerTest extends TestCase
{
    use FactoryTrait;
    use IntegrationTestTrait;

    public ProjectsTable $Projects;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'plugin.Tasks.Projects',
        'plugin.Tasks.ProjectSections',
        'app.Users',
        'plugin.Tasks.Tasks',
        'app.Labels',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->Projects = $this->fetchTable('Tasks.Projects');
    }

    public function testView(): void
    {
        $home = $this->makeProject('Home', 1, 0, ['archived' => true]);
        $this->makeTask('first post', $home->id, 0);

        $this->login();
        $this->get("/projects/{$home->slug}");

        $this->assertResponseOk();
        $this->assertSame($home->id, $this->viewVariable('project')->id);
        $this->assertCount(1, $this->viewVariable('tasks'));
    }

    public function testViewHtmx(): void
    {
        $home = $this->makeProject('Home', 1, 0, ['archived' => true]);
        $this->makeTask('first post', $home->id, 0);

        $this->login();
        $this->useHtmx();
        $this->get("/projects/{$home->slug}");

        $this->assertResponseOk();
        // Important to close menus like move project and reschedule.
        $this->assertHeader('Hx-Trigger', 'close');
        $this->assertSame($home->id, $this->viewVariable('project')->id);
        $this->assertCount(1, $this->viewVariable('tasks'));
    }

    public function testViewSlugScope(): void
    {
        $otherHome = $this->makeProject('Home', 2, 0);
        $this->makeTask('first post', $otherHome->id, 0);

        $home = $this->makeProject('Home', 1, 0);
        $this->makeTask('first post', $home->id, 0);
        $this->makeTask('second post', $home->id, 0);

        $this->login();
        $this->get("/projects/{$home->slug}");

        $this->assertResponseOk();
        $this->assertSame($home->id, $this->viewVariable('project')->id);
        $this->assertCount(2, $this->viewVariable('tasks'));
    }

    public function testViewCompleted(): void
    {
        $home = $this->makeProject('Home', 1, 0, ['archived' => true]);
        $this->makeTask('first post', $home->id, 0);
        $this->makeTask('done', $home->id, 0, ['completed' => true]);

        $this->login();
        $this->get("/projects/{$home->slug}/?completed=1");

        $this->assertResponseOk();
        $this->assertSame($home->id, $this->viewVariable('project')->id);
        $this->assertCount(1, $this->viewVariable('tasks'));
    }

    public function testAddGet(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->configRequest([
            'headers' => ['Referer' => '/tasks/today'],
        ]);
        $this->get('/projects/add');

        $this->assertResponseOk();
        $this->assertTemplate('Projects/add');
        $this->assertSame('/tasks/today', $this->viewVariable('referer'));
    }

    public function testAddAppendRanking(): void
    {
        $this->makeProject('Home', 1, 0, ['archived' => true]);

        $this->login();
        $this->enableCsrfToken();
        $this->post('/projects/add', [
            'name' => 'second',
            'color' => '8',
        ]);
        $this->assertRedirect('/tasks/today');

        $project = $this->Projects->find()->where(['Projects.slug' => 'second'])->firstOrFail();
        $this->assertEquals('second', $project->name);
        $this->assertEquals(1, $project->ranking);
    }

    public function testAddReservedSlug(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->post('/projects/add', [
            'name' => 'add',
            'color' => '8',
        ]);
        $this->assertRedirect('/tasks/today');

        $project = $this->Projects->find()->first();
        $this->assertEquals('add', $project->name);
        $this->assertNotEquals('add', $project->slug);
    }

    public function testAddSlugScopedToUser()
    {
        $this->makeProject('Home', 2, 0);
        $this->login();
        $this->enableCsrfToken();
        $this->post('/projects/add', [
            'name' => 'Home',
            'color' => '8',
        ]);
        $this->assertRedirect('/tasks/today');
        $homeCount = $this->Projects->find()->where(['Projects.slug' => 'home'])->count();
        $this->assertSame(2, $homeCount);

        $new = $this->Projects->find()->where([
            'Projects.slug' => 'home',
            'Projects.user_id' => 1,
        ])->first();
        $this->assertNotEmpty($new);
    }

    public function testEdit(): void
    {
        $home = $this->makeProject('Home', 1, 0, ['archived' => true]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$home->slug}/edit", [
            'name' => 'Home too',
            'color' => '8',
        ]);
        $this->assertRedirect('/projects/home-too');

        $project = $this->Projects->find()->where(['Projects.id' => $home->id])->firstOrFail();
        $this->assertEquals('Home too', $project->name);
        $this->assertEquals('8', $project->color);
        $this->assertEquals(0, $project->ranking);
    }

    public function testEditValidationError(): void
    {
        $home = $this->makeProject('Home', 1, 0, ['archived' => true]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$home->slug}/edit", [
            'name' => 'Home too',
            'color' => 'not a color',
            'referer' => '/tasks/upcoming',
        ]);
        $this->assertResponseOk();

        $this->assertSame('/tasks/upcoming', $this->viewVariable('referer'));
        $this->assertArrayHasKey('color', $this->viewVariable('errors'));
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
        $this->assertResponseCode(404);
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
        $this->assertRedirect('/tasks/today');
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
        $this->assertResponseCode(404);
        $this->assertTrue($this->Projects->exists(['slug' => $home->slug]));
    }

    public function testArchived()
    {
        $this->makeProject('Home', 1, 0, ['archived' => true]);
        $this->makeProject('Work', 1, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->get('/projects/archived');

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
        $this->assertRedirect('/tasks/today');

        $result = $this->Projects->find()->where(['id' => $home->id])->first();
        $this->assertTrue($result->archived);
    }

    public function testArchivePermission()
    {
        $home = $this->makeProject('Home', 2, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$home->slug}/archive");
        $this->assertResponseCode(404);
    }

    public function testUnarchive()
    {
        $home = $this->makeProject('Home', 1, 0, ['archived' => true]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$home->slug}/unarchive");
        $this->assertRedirect('/tasks/today');

        $result = $this->Projects->find()->where(['id' => $home->id])->first();
        $this->assertFalse($result->archived);
    }

    public function testUnArchivePermission()
    {
        $home = $this->makeProject('Home', 2, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$home->slug}/unarchive");
        $this->assertResponseCode(404);
    }

    public function testMovePermission()
    {
        $this->makeProject('Home', 1, 0);
        $work = $this->makeProject('Work', 2, 3);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$work->slug}/move", [
            'ranking' => 0,
        ]);
        $this->assertResponseCode(404);
    }

    public function testMoveNoData()
    {
        $home = $this->makeProject('Home', 1, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->post("/projects/{$home->slug}/move");
        $this->assertRedirect('/tasks/today');
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
        $this->assertRedirect('/tasks/today');

        $results = $this->Projects->find()->orderByAsc('ranking')->toArray();
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
        $this->assertRedirect('/tasks/today');

        $results = $this->Projects->find()->orderByAsc('ranking')->toArray();
        $expected = [$fun->id, $home->id, $work->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }

    public function testReorderOk()
    {
        $home = $this->makeProject('Home', 1, 0);
        $work = $this->makeProject('Work', 1, 3);
        $fun = $this->makeProject('Fun', 1, 6);

        $this->login();
        $this->enableCsrfToken();
        $this->post('/projects/reorder', [
            'id' => [$fun->id, $work->id, $home->id],
        ]);
        $this->assertResponseOk();
        $this->assertResponseContains('Home');
        $this->assertResponseContains('Work');

        $results = $this->Projects->find()->orderByAsc('ranking')->toArray();
        $expected = [$fun->id, $work->id, $home->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }

    public function testReorderPermissions()
    {
        $home = $this->makeProject('Home', 1, 0);
        $work = $this->makeProject('Work', 1, 3);
        $nope = $this->makeProject('Other Home', 2, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->post('/projects/reorder', [
            'id' => [$nope->id, $work->id, $home->id],
        ]);
        $this->assertResponseCode(400);

        $results = $this->Projects->find()
            ->orderByAsc('user_id')
            ->orderByAsc('ranking')
            ->toArray();
        $expected = [$home->id, $work->id, $nope->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }

    public function testReorderPartialUpdates()
    {
        $home = $this->makeProject('Home', 1, 0);
        $work = $this->makeProject('Work', 1, 3);
        $other = $this->makeProject('Other', 1, 6);

        $this->login();
        $this->enableCsrfToken();
        $this->post('/projects/reorder', [
            'id' => [$other->id, $work->id],
        ]);
        $this->assertResponseOk();

        $results = $this->Projects->find()
            ->orderByAsc('ranking')
            ->orderByAsc('name')
            ->toArray();
        $expected = [$home->id, $other->id, $work->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }
}

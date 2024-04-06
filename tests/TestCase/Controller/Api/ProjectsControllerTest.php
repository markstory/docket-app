<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use App\Model\Table\ProjectsTable;
use App\Test\TestCase\FactoryTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Api\ProjectsController Test Case
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
        'app.ApiTokens',
        'app.Projects',
        'app.ProjectSections',
        'app.Users',
        'app.Tasks',
        'app.Labels',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->Projects = $this->fetchTable('Projects');
    }

    public function testIndexPermissions(): void
    {
        $home = $this->makeProject('Home', 1, 0);
        $this->makeProject('Work', 2, 1);

        $this->loginApi(1);
        $this->get('/api/projects');

        $this->assertResponseOk();

        $projects = $this->viewVariable('projects')->toArray();
        $this->assertCount(1, $projects);
        $this->assertEquals($home->slug, $projects[0]->slug);
    }

    public function testIndex(): void
    {
        $home = $this->makeProject('Home', 1, 0);
        $work = $this->makeProject('Work', 1, 1);
        $this->makeTask('first post', $home->id, 0);
        $this->loginApi(1);

        $this->get('/api/projects');

        $this->assertResponseOk();

        $projects = $this->viewVariable('projects')->toArray();
        $this->assertCount(2, $projects);
        $this->assertEquals($home->slug, $projects[0]->slug);
        $this->assertEquals($work->slug, $projects[1]->slug);
    }

    public function testViewApiToken(): void
    {
        $home = $this->makeProject('Home', 1, 0, ['archived' => true]);
        $this->makeTask('first post', $home->id, 0);
        $this->loginApi(1);

        $this->get("/api/projects/{$home->slug}");

        $this->assertResponseOk();
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
        $this->loginApi(1);

        $this->get("/api/projects/{$home->slug}");

        $this->assertResponseOk();
        $this->assertSame($home->id, $this->viewVariable('project')->id);
        $this->assertCount(2, $this->viewVariable('tasks'));
    }

    public function testViewCompleted(): void
    {
        $home = $this->makeProject('Home', 1, 0, ['archived' => true]);
        $this->makeTask('first post', $home->id, 0);
        $this->makeTask('done', $home->id, 0, ['completed' => true]);
        $this->loginApi(1);

        $this->get("/api/projects/{$home->slug}/?completed=1");

        $this->assertResponseOk();
        $this->assertSame($home->id, $this->viewVariable('project')->id);
        $this->assertCount(1, $this->viewVariable('tasks'));
    }

    public function testAddAppendRanking(): void
    {
        $this->makeProject('Home', 1, 0, ['archived' => true]);

        $this->loginApi(1);
        $this->post('/api/projects/add', [
            'name' => 'second',
            'color' => '8',
        ]);
        $this->assertResponseOk();

        $project = $this->Projects->find()->where(['Projects.slug' => 'second'])->firstOrFail();
        $this->assertEquals('second', $project->name);
        $this->assertEquals(1, $project->ranking);
    }

    public function testAdd(): void
    {
        $this->makeProject('Home', 1, 0, ['archived' => true]);
        $this->loginApi(1);

        $this->post('/api/projects/add', [
            'name' => 'second',
            'color' => '8',
        ]);
        $this->assertResponseOk();

        $project = $this->viewVariable('project');
        $this->assertEquals('second', $project->name);
        $this->assertEquals(1, $project->ranking);

        $project = $this->Projects->find()->where(['Projects.slug' => 'second'])->firstOrFail();
        $this->assertNotEmpty($project->id);
    }

    public function testAddReservedSlug(): void
    {
        $this->loginApi(1);
        $this->post('/api/projects/add', [
            'name' => 'add',
            'color' => '8',
        ]);
        $this->assertResponseOk();

        $project = $this->Projects->find()->first();
        $this->assertEquals('add', $project->name);
        $this->assertNotEquals('add', $project->slug);
    }

    public function testAddSlugScopedToUser()
    {
        $this->makeProject('Home', 2, 0);
        $this->loginApi(1);

        $this->post('/api/projects/add', [
            'name' => 'Home',
            'color' => '8',
        ]);
        $this->assertResponseOk();
        $homeCount = $this->Projects->find()->where(['Projects.slug' => 'home'])->count();
        $this->assertSame(2, $homeCount);

        $new = $this->Projects->find()->where([
            'Projects.slug' => 'home',
            'Projects.user_id' => 1,
        ])->first();
        $this->assertNotEmpty($new);
    }

    public function testEditApiToken(): void
    {
        $home = $this->makeProject('Home', 1, 0, ['archived' => true]);
        $this->loginApi(1);

        $this->post("/api/projects/{$home->slug}/edit", [
            'name' => 'Home too',
            'color' => '8',
        ]);
        $this->assertResponseOk();

        $this->assertNotEmpty($this->viewVariable('project'));
        $this->assertEmpty($this->viewVariable('errors'));

        $project = $this->Projects->find()->where(['Projects.id' => $home->id])->firstOrFail();
        $this->assertEquals('Home too', $project->name);
        $this->assertEquals('8', $project->color);
        $this->assertEquals(0, $project->ranking);
    }

    public function testEditValidationError(): void
    {
        $home = $this->makeProject('Home', 1, 0, ['archived' => true]);
        $this->loginApi(1);

        $this->post("/api/projects/{$home->slug}/edit", [
            'name' => 'Home too',
            'color' => 'not a color',
            'referer' => '/tasks/upcoming',
        ]);

        $this->assertResponseCode(422);
        $this->assertArrayHasKey('color', $this->viewVariable('errors'));
    }

    public function testEditPermission(): void
    {
        $home = $this->makeProject('Home', 2, 0, ['archived' => true]);
        $this->loginApi(1);

        $this->post("/api/projects/{$home->slug}/edit", [
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
        $this->loginApi(1);

        $this->post("/api/projects/{$home->slug}/delete");

        $this->assertResponseCode(204);
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
        $this->loginApi(1);

        $this->post("/api/projects/{$home->slug}/delete");
        $this->assertResponseCode(404);
        $this->assertTrue($this->Projects->exists(['slug' => $home->slug]));
    }

    public function testArchived()
    {
        $this->makeProject('Home', 1, 0, ['archived' => true]);
        $this->makeProject('Work', 1, 0);
        $this->loginApi(1);

        $this->get('/api/projects/archived');

        $this->assertResponseOk();
        $archived = $this->viewVariable('archived');
        $this->assertCount(1, $archived);
    }

    public function testArchive()
    {
        $home = $this->makeProject('Home', 1, 0);
        $this->loginApi(1);

        $this->post("/api/projects/{$home->slug}/archive");
        $this->assertResponseOk();

        $result = $this->Projects->find()->where(['id' => $home->id])->first();
        $this->assertTrue($result->archived);
    }

    public function testArchivePermission()
    {
        $home = $this->makeProject('Home', 2, 0);
        $this->loginApi(1);

        $this->post("/api/projects/{$home->slug}/archive");
        $this->assertResponseCode(404);
    }

    public function testUnarchive()
    {
        $home = $this->makeProject('Home', 1, 0, ['archived' => true]);
        $this->loginApi(1);

        $this->post("/api/projects/{$home->slug}/unarchive");

        $this->assertResponseOk();
        $result = $this->Projects->find()->where(['id' => $home->id])->first();
        $this->assertFalse($result->archived);
    }

    public function testUnArchivePermission()
    {
        $home = $this->makeProject('Home', 2, 0);
        $this->loginApi(1);

        $this->post("/api/projects/{$home->slug}/unarchive");

        $this->assertResponseCode(404);
    }

    public function testMovePermission()
    {
        $home = $this->makeProject('Home', 1, 0);
        $work = $this->makeProject('Work', 2, 3);
        $this->loginApi(1);

        $this->post("/api/projects/{$work->slug}/move", [
            'ranking' => 0,
        ]);
        $this->assertResponseCode(404);
    }

    public function testMoveNoData()
    {
        $home = $this->makeProject('Home', 1, 0);
        $this->loginApi(1);

        $this->post("/api/projects/{$home->slug}/move");
        $this->assertResponseCode(400);
        $this->assertNotEmpty($this->viewVariable('errors'));
    }

    public function testMove()
    {
        $home = $this->makeProject('Home', 1, 0);
        $work = $this->makeProject('Work', 1, 3);
        $fun = $this->makeProject('Fun', 1, 6);
        $this->loginApi(1);

        $this->post("/api/projects/{$home->slug}/move", [
            'ranking' => 1,
        ]);
        $this->assertResponseOk();
        $this->assertResponseContains('"home"');

        $results = $this->Projects->find()->orderByAsc('ranking')->toArray();
        $expected = [$work->id, $home->id, $fun->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }

    public function testMoveDown()
    {
        $home = $this->makeProject('Home', 1, 0);
        $work = $this->makeProject('Work', 1, 3);
        $fun = $this->makeProject('Fun', 1, 6);
        $this->loginApi(1);

        $this->post("/api/projects/{$home->slug}/move", [
            'ranking' => 1,
        ]);
        $this->assertResponseOk();

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
        $this->loginApi(1);

        $this->post("/api/projects/{$fun->slug}/move", [
            'ranking' => 0,
        ]);
        $this->assertResponseOk();

        $results = $this->Projects->find()->orderByAsc('ranking')->toArray();
        $expected = [$fun->id, $home->id, $work->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }
}

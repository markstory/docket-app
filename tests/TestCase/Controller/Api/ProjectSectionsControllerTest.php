<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use App\Test\TestCase\FactoryTrait;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\ProjectSectionsController Test Case
 *
 * @uses \App\Controller\ProjectSectionsController
 */
class ProjectSectionsControllerTest extends TestCase
{
    use FactoryTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    protected array $fixtures = [
        'app.Users',
        'app.Projects',
        'app.ProjectSections',
        'app.Tasks',
    ];

    /**
     * @var \App\Model\Table\ProjectSectionsTable
     */
    protected $ProjectSections;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ProjectSections = TableRegistry::get('ProjectSections');
    }

    public function testAdd()
    {
        $project = $this->makeProject('Home', 1);
        $this->loginApi(1);

        $this->post("/api/projects/{$project->slug}/sections", [
            'name' => 'Day Trips',
        ]);
        $this->assertResponseOk();

        $section = $this->ProjectSections->find()->firstOrFail();
        $this->assertEquals('Day Trips', $section->name);
        $this->assertEquals($project->id, $section->project_id);
        $this->assertEquals(0, $section->ranking);
    }

    public function testAddPermissions()
    {
        $project = $this->makeProject('Home', 2);
        $this->loginApi(1);

        $this->post("/api/projects/{$project->slug}/sections", [
            'name' => 'Day Trips',
        ]);
        $this->assertResponseCode(404);

        $count = $this->ProjectSections->find()->count();
        $this->assertEquals(0, $count);
    }

    public function testAddValidationError()
    {
        $project = $this->makeProject('Home', 1);
        $this->loginApi(1);

        $this->post("/api/projects/{$project->slug}/sections/", [
            'name' => '',
        ]);
        $this->assertResponseCode(422);

        $count = $this->ProjectSections->find()->count();
        $this->assertEquals(0, $count);
        $this->assertNotEmpty($this->viewVariable('errors'));
    }

    public function testEdit()
    {
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Day trips', $project->id);
        $this->loginApi(1);

        $this->post("/api/projects/{$project->slug}/sections/{$section->id}/edit", [
            'name' => 'Reading list',
            'ranking' => 99,
        ]);
        $this->assertResponseOk();

        $updated = $this->ProjectSections->get($section->id);
        $this->assertEquals('Reading list', $updated->name);
        $this->assertEquals($section->ranking, $updated->ranking);
    }

    public function testEditPermissions()
    {
        $project = $this->makeProject('Home', 2);
        $section = $this->makeProjectSection('Day trips', $project->id);
        $this->loginApi(1);

        $this->post("/api/projects/{$project->slug}/sections/{$section->id}/edit", [
            'name' => 'Reading list',
        ]);
        $this->assertResponseCode(404);
    }

    public function testEditValidationFailure()
    {
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Day trips', $project->id);
        $this->loginApi(1);

        $this->post("/api/projects/{$project->slug}/sections/{$section->id}/edit", [
            'name' => '',
            'ranking' => 99,
        ]);
        $this->assertResponseCode(422);
        $this->assertNotEmpty($this->viewVariable('errors'));
    }

    public function testView()
    {
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Day trips', $project->id);
        $this->loginApi(1);

        $this->get("/api/projects/{$project->slug}/sections/{$section->id}/view");

        $this->assertResponseOk();
        $this->assertNotEmpty($this->viewVariable('section'));
    }

    public function testViewPermissions()
    {
        $project = $this->makeProject('Home', 2);
        $section = $this->makeProjectSection('Day trips', $project->id);
        $this->loginApi(1);

        $this->post("/api/projects/{$project->slug}/sections/{$section->id}/view");
        $this->assertResponseCode(404);
    }

    public function testDelete()
    {
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Day trips', $project->id);
        $this->loginApi(1);

        $this->post("/api/projects/{$project->slug}/sections/{$section->id}/delete");
        $this->assertResponseOk();

        $count = $this->ProjectSections->find()->count();
        $this->assertEquals($count, 0);
    }

    public function testDeletePermissions()
    {
        $project = $this->makeProject('Home', 2);
        $section = $this->makeProjectSection('Day trips', $project->id);
        $this->loginApi(1);

        $this->post("/api/projects/{$project->slug}/sections/{$section->id}/delete");
        $this->assertResponseCode(404);
    }

    public function testDeleteUpdateTasks()
    {
        $this->disableErrorHandlerMiddleware();
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Day trips', $project->id);
        $task = $this->makeTask('first', $project->id, 0, [
            'section_id' => $section->id,
        ]);
        $this->loginApi(1);

        $this->post("/api/projects/{$project->slug}/sections/{$section->id}/delete");
        $this->assertResponseOk();

        $count = $this->ProjectSections->find()->count();
        $this->assertEquals($count, 0);

        $updated = $this->ProjectSections->Tasks->get($task->id);
        $this->assertNull($updated->section_id);
    }

    public function testMovePermissions()
    {
        $project = $this->makeProject('Home', 2);
        $section = $this->makeProjectSection('Day trips', $project->id);
        $this->loginApi(1);

        $this->post("/api/projects/{$project->slug}/sections/{$section->id}/move", [
            'ranking' => 0,
        ]);
        $this->assertResponseCode(404);
    }

    public function testMoveNoData()
    {
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Day trips', $project->id);
        $this->loginApi(1);

        $this->post("/api/projects/{$project->slug}/sections/{$section->id}/move");
        $this->assertResponseCode(422);
    }

    public function testMoveDown()
    {
        $project = $this->makeProject('Home', 1);
        $reading = $this->makeProjectSection('Reading', $project->id, 0);
        $repairs = $this->makeProjectSection('Repairs', $project->id, 1);
        $cleaning = $this->makeProjectSection('Cleaning', $project->id, 2);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$project->slug}/sections/{$reading->id}/move", [
            'ranking' => 2,
        ]);
        $this->assertRedirect('/projects/home');

        $results = $this->ProjectSections->find()->orderByAsc('ranking')->toArray();
        $expected = [$repairs->id, $cleaning->id, $reading->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }

    public function testMoveUp()
    {
        $project = $this->makeProject('Home', 1);
        $reading = $this->makeProjectSection('Reading', $project->id, 0);
        $repairs = $this->makeProjectSection('Repairs', $project->id, 1);
        $cleaning = $this->makeProjectSection('Cleaning', $project->id, 2);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$project->slug}/sections/{$cleaning->id}/move", [
            'ranking' => 0,
        ]);
        $this->assertRedirect('/projects/home');

        $results = $this->ProjectSections->find()->orderByAsc('ranking')->toArray();
        $expected = [$cleaning->id, $reading->id, $repairs->id];
        $this->assertCount(count($expected), $results);
        foreach ($expected as $i => $id) {
            $this->assertEquals($id, $results[$i]->id);
        }
    }
}

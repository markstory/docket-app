<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

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
        $this->ProjectSections = $this->fetchTable('ProjectSections');
    }

    public function testAdd()
    {
        $project = $this->makeProject('Home', 1);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$project->slug}/sections", [
            'name' => 'Day Trips',
        ]);
        $this->assertRedirect('/tasks/today');
        $this->assertFlashElement('flash/success');

        $section = $this->ProjectSections->find()->firstOrFail();
        $this->assertEquals('Day Trips', $section->name);
        $this->assertEquals($project->id, $section->project_id);
        $this->assertEquals(0, $section->ranking);
    }

    public function testAddPermissions()
    {
        $project = $this->makeProject('Home', 2);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$project->slug}/sections", [
            'name' => 'Day Trips',
        ]);
        $this->assertResponseCode(404);

        $count = $this->ProjectSections->find()->count();
        $this->assertEquals(0, $count);
    }

    public function testAddValidationError()
    {
        $project = $this->makeProject('Home', 1);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$project->slug}/sections/", [
            'name' => '',
        ]);
        $this->assertResponseCode(200);
        $this->assertFlashElement('flash/error');

        $count = $this->ProjectSections->find()->count();
        $this->assertEquals(0, $count);
    }

    public function testEdit()
    {
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Day trips', $project->id);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$project->slug}/sections/{$section->id}/edit", [
            'name' => 'Reading list',
            'ranking' => 99,
        ]);
        $this->assertResponseCode(302);
        $this->assertFlashElement('flash/success');

        $updated = $this->ProjectSections->get($section->id);
        $this->assertEquals('Reading list', $updated->name);
        $this->assertEquals($section->ranking, $updated->ranking);
    }

    public function testEditPermissions()
    {
        $project = $this->makeProject('Home', 2);
        $section = $this->makeProjectSection('Day trips', $project->id);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$project->slug}/sections/{$section->id}/edit", [
            'name' => 'Reading list',
        ]);
        $this->assertResponseCode(404);
    }

    public function testEditValidationFailure()
    {
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Day trips', $project->id);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$project->slug}/sections/{$section->id}/edit", [
            'name' => '',
            'ranking' => 99,
        ]);
        $this->assertResponseCode(200);
        $this->assertFlashElement('flash/error');
        $this->assertNotEmpty($this->viewVariable('errors'));
    }

    public function testEditGet()
    {
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Day trips', $project->id);

        $this->login();
        $this->get("/projects/{$project->slug}/sections/{$section->id}/edit");

        $this->assertResponseCode(200);
        $this->assertContentType('text/html');
        $this->assertNotEmpty($this->viewVariable('project'));
        $this->assertNotEmpty($this->viewVariable('section'));
    }

    public function testView()
    {
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Day trips', $project->id);

        $this->login();
        $this->enableCsrfToken();
        $this->get("/projects/{$project->slug}/sections/{$section->id}/view");

        $this->assertResponseOk();
        $this->assertNotEmpty($this->viewVariable('project'));
        $this->assertNotEmpty($this->viewVariable('section'));
    }

    public function testViewPermissions()
    {
        $project = $this->makeProject('Home', 2);
        $section = $this->makeProjectSection('Day trips', $project->id);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$project->slug}/sections/{$section->id}/view");
        $this->assertResponseCode(404);
    }

    public function testDeleteConfirm()
    {
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Day trips', $project->id);

        $this->login();
        $this->get("/projects/{$project->slug}/sections/{$section->id}/delete/confirm");

        $this->assertResponseOk();
        $this->assertNotEmpty($this->viewVariable('project'));
        $this->assertNotEmpty($this->viewVariable('section'));
    }

    public function testDelete()
    {
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Day trips', $project->id);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$project->slug}/sections/{$section->id}/delete");
        $this->assertRedirect('/projects/home');

        $count = $this->ProjectSections->find()->count();
        $this->assertEquals($count, 0);
    }

    public function testDeletePermissions()
    {
        $project = $this->makeProject('Home', 2);
        $section = $this->makeProjectSection('Day trips', $project->id);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$project->slug}/sections/{$section->id}/delete");
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

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$project->slug}/sections/{$section->id}/delete");
        $this->assertRedirect('/projects/home');

        $count = $this->ProjectSections->find()->count();
        $this->assertEquals($count, 0);

        $updated = $this->ProjectSections->Tasks->get($task->id);
        $this->assertNull($updated->section_id);
    }

    public function testMovePermissions()
    {
        $project = $this->makeProject('Home', 2);
        $section = $this->makeProjectSection('Day trips', $project->id);

        $this->login();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
        $this->post("/projects/{$project->slug}/sections/{$section->id}/move", [
            'ranking' => 0,
        ]);
        $this->assertResponseCode(404);
    }

    public function testMoveNoData()
    {
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Day trips', $project->id);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$project->slug}/sections/{$section->id}/move");
        $this->assertRedirect('/projects/home');
        $this->assertFlashElement('flash/error');
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

    public function testOptionsNotFound()
    {
        $project = $this->makeProject('Home', 1);
        $this->makeProjectSection('Reading', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->get('/projectsections/options?project_id=999');
        $this->assertResponseCode(404);
    }

    public function testOptionsOk()
    {
        $project = $this->makeProject('Home', 1);
        $this->makeProjectSection('Reading', $project->id, 0);

        $this->login();
        $this->enableCsrfToken();
        $this->get("/projectsections/options?project_id={$project->id}");
        $this->assertResponseOk();
        $this->assertResponseContains('Reading</option>');
    }
}

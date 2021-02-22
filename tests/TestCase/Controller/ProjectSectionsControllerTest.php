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
    protected $fixtures = [
        'app.Users',
        'app.ProjectSections',
        'app.Projects',
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
        $this->assertResponseCode(403);

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
        $this->assertResponseCode(403);
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

    public function testEditProjectPermissionError()
    {
        $other = $this->makeProject('Other Home', 2);
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Day trips', $project->id);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$project->slug}/sections/{$section->id}/edit", [
            'name' => 'Reading list',
            'project_id' => $other->id,
        ]);
        $this->assertResponseCode(403);
    }

    public function testArchive()
    {
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Day trips', $project->id);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$project->slug}/sections/{$section->id}/archive");
        $this->assertRedirect('/projects/home');

        $updated = $this->ProjectSections->get($section->id);
        $this->assertTrue($updated->archived);
    }

    public function testArchivePermission()
    {
        $project = $this->makeProject('Home', 2);
        $section = $this->makeProjectSection('Day trips', $project->id);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$project->slug}/sections/{$section->id}/archive");
        $this->assertResponseCode(403);
    }

    public function testUnarchive()
    {
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Day trips', $project->id, 0, [
            'archived' => true
        ]);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$project->slug}/sections/{$section->id}/unarchive");
        $this->assertRedirect('/projects/home');

        $updated = $this->ProjectSections->get($section->id);
        $this->assertFalse($updated->archived);
    }

    public function testUnarchivePermission()
    {
        $project = $this->makeProject('Home', 2);
        $section = $this->makeProjectSection('Day trips', $project->id);

        $this->login();
        $this->enableCsrfToken();
        $this->post("/projects/{$project->slug}/sections/{$section->id}/unarchive");
        $this->assertResponseCode(403);
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
        $this->assertResponseCode(403);
    }

    public function testMoveNoData()
    {
        $this->markTestIncomplete();
    }

    public function testMoveDown()
    {
        $this->markTestIncomplete();
    }

    public function testMoveUp()
    {
        $this->markTestIncomplete();
    }
}

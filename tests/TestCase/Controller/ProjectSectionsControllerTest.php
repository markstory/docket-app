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
        $this->post("/projects/{$project->slug}/sections", [
            'name' => '',
        ]);
        $this->assertResponseCode(200);
        $this->assertFlashElement('flash/error');
        $this->assertNotEmpty($this->viewVariable('errors'));
    }

    public function testEdit()
    {
        $this->markTestIncomplete();
    }

    public function testEditPermissions()
    {
        $this->markTestIncomplete();
    }

    public function testEditValidationFailure()
    {
        $this->markTestIncomplete();
    }

    public function testArchive()
    {
        $this->markTestIncomplete();
    }

    public function testArchivePermission()
    {
        $this->markTestIncomplete();
    }

    public function testUnarchive()
    {
        $this->markTestIncomplete();
    }

    public function testUnarchivePermission()
    {
        $this->markTestIncomplete();
    }

    public function testDelete()
    {
        $this->markTestIncomplete();
    }

    public function testDeletePermissions()
    {
        $this->markTestIncomplete();
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

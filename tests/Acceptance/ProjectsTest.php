<?php
declare(strict_types=1);

namespace App\Test\Acceptance;

use Cake\ORM\TableRegistry;

class ProjectsTest extends AcceptanceTestCase
{
    public function testCreate()
    {
        $client = $this->login();
        $client->get('/tasks/today');
        $client->waitFor('[data-testid="loggedin"]');

        $link = $client->getCrawler()->selectLink('New Project')->link();
        $client->click($link);

        // Wait for page to load
        $client->waitFor('.modal');
        $client->submitForm('Save', [
            'name' => 'New project',
        ]);

        $projects = TableRegistry::get('Projects');
        $project = $projects->find()->first();
        $this->assertNotEmpty($project, 'No project saved');
        $this->assertEquals('New project', $project->name);
    }
}

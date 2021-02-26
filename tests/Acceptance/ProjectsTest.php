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
        $client->waitFor('input[name="name"]');
        $client->submitForm('Save', [
            'name' => 'New project',
        ]);

        $projects = TableRegistry::get('Projects');
        $project = $projects->find()->first();
        $this->assertNotEmpty($project, 'No project saved');
        $this->assertEquals('New project', $project->name);
    }

    public function testTasksInSections()
    {
        $project = $this->makeProject('Home', 1);
        $movies = $this->makeProjectSection('movies', $project->id, 0);
        $books = $this->makeProjectSection('books', $project->id, 1);
        $this->makeTask('robocop', $project->id, 0, [
            'section_id' => $movies->id
        ]);
        $this->makeTask('matrix', $project->id, 0, [
            'section_id' => $books->id
        ]);

        $client = $this->login();
        $client->get('/projects/home');
        $client->waitFor('[data-testid="loggedin"]');

        $headings = $client->getCrawler()->filter('[data-testid="section"] h3');
        $this->assertEquals(2, count($headings));
        $this->assertEquals(['movies', 'books'], $headings->extract(['_text']));

        $tasks = $client->getCrawler()->filter('.task-group');
        $this->assertEquals(3, count($tasks));
    }
}

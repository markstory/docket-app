<?php
declare(strict_types=1);

namespace App\Test\Acceptance;

use Cake\ORM\TableRegistry;

class ProjectsTest extends AcceptanceTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->Projects = TableRegistry::get('Projects');
    }

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

        $project = $this->Projects->find()->first();
        $this->assertNotEmpty($project, 'No project saved');
        $this->assertEquals('New project', $project->name);
    }

    public function testDelete()
    {
        $this->makeProject('Home', 1);
        $client = $this->login();
        $client->get('/projects/home');
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Open the header menu
        $headerMenu = $crawler->filter('.heading .button-icon')->first();
        $headerMenu->click();
        $client->waitFor('[data-reach-menu-item]');

        // Click delete
        $this->clickWithMouse('.delete[data-reach-menu-item]');

        // Click proceed in the modal.
        $client->waitFor('[aria-modal="true"]');
        $button = $crawler->filter('[aria-modal] [data-testid="confirm-proceed"]')->first();
        $button->click();

        $this->assertEquals(0, $this->Projects->find()->count());
    }

    public function testTasksRenderInSections()
    {
        $project = $this->makeProject('Home', 1);
        $movies = $this->makeProjectSection('movies', $project->id, 0);
        $books = $this->makeProjectSection('books', $project->id, 1);
        $this->makeTask('robocop', $project->id, 0, [
            'section_id' => $movies->id,
        ]);
        $this->makeTask('matrix', $project->id, 0, [
            'section_id' => $books->id,
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

    public function testAddSection()
    {
        $project = $this->makeProject('Home', 1);

        $client = $this->login();
        $client->get('/projects/home');
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        $headerMenu = $crawler->filter('.heading .button-icon')->first();
        // Open the header menu
        $headerMenu->click();
        $client->waitFor('[data-reach-menu-item]');

        $this->clickWithMouse('[data-testid="add-section"]');

        $form = $crawler->filter('.section-quickform')->form();
        $form->get('name')->setValue('books to read');
        $crawler->filter('[data-testid="save-section"]')->click();

        $section = $this->Projects->Sections->find()->firstOrFail();
        $this->assertEquals('books to read', $section->name);
        $this->assertSame($project->id, $section->project_id);
    }

    public function testEditSection()
    {
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Books', $project->id);

        $client = $this->login();
        $client->get('/projects/home');
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Open the header menu
        $sectionMenu = $crawler->filter('.section-container [aria-label="Section actions"]')->first();
        $sectionMenu->click();
        $client->waitFor('[data-reach-menu-item]');

        // Click the edit action
        $this->clickWithMouse('.edit[data-reach-menu-item]');

        // Update the section name.
        $client->waitFor('.section-quickform');
        $form = $crawler->filter('.section-quickform')->form();
        $form->get('name')->setValue('books to read');
        $crawler->filter('[data-testid="save-section"]')->click();

        $section = $this->Projects->Sections->find()->firstOrFail();
        $this->assertEquals('books to read', $section->name);
        $this->assertSame($project->id, $section->project_id);
    }

    public function testDeleteSection()
    {
        $project = $this->makeProject('Home', 1);
        $this->makeProjectSection('Books', $project->id);

        $client = $this->login();
        $client->get('/projects/home');
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Open the header menu
        $sectionMenu = $crawler->filter('.section-container [aria-label="Section actions"]')->first();
        $sectionMenu->click();
        $client->waitFor('[data-reach-menu-item]');

        // Click the delete action
        $this->clickWithMouse('.delete[data-reach-menu-item]');

        // Click proceed in the modal.
        $client->waitFor('[aria-modal="true"]');
        $button = $crawler->filter('[aria-modal] [data-testid="confirm-proceed"]')->first();
        $button->click();

        $this->assertEquals(0, $this->Projects->Sections->find()->count());
    }

    public function testDragTaskToSection()
    {
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Books', $project->id);
        $one = $this->makeTask('First', $project->id, 0);
        $this->makeTask('Two', $project->id, 0, ['section_id' => $section->id]);

        $client = $this->login();
        $client->get('/projects/home');
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Get the last task
        $last = $client->getCrawler()->filter('.task-group .dnd-handle')->getElement(1);
        $mouse = $client->getMouse();

        // Do a drag from the top to the bottom
        $mouse->mouseDownTo('.task-group .dnd-item:first-child .dnd-handle')
            ->mouseMove($last->getCoordinates(), 0, 20)
            ->mouseUp($last->getCoordinates(), 0, 20);
        $client->waitFor('.flash-message');

        $task = $this->Projects->Tasks->get($one->id);
        $this->assertSame($section->id, $task->section_id);
    }

    public function testAddTaskToSection()
    {
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Books', $project->id);

        $client = $this->login();
        $client->get('/projects/home');
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Click add task in the section.
        $addTask = $crawler->filter('.section-container [data-testid="add-task"]')->first();
        $addTask->click();
        $client->waitFor('.task-quickform');

        $title = $crawler->filter('.task-quickform .smart-task-input input');
        $title->sendKeys('A new task');

        $button = $client->getCrawler()->filter('[data-testid="save-task"]');
        $button->click();

        $task = $this->Projects->Tasks->find()->firstOrFail();
        $this->assertEquals('A new task', $task->title);
        $this->assertEquals($project->id, $task->project_id);
        $this->assertEquals($section->id, $task->section_id);
    }
}

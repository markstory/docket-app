<?php
declare(strict_types=1);

namespace App\Test\Acceptance;

use Symfony\Component\Panther\Client;
use Tasks\Model\Table\ProjectsTable;

class ProjectsTest extends AcceptanceTestCase
{
    protected ProjectsTable $Projects;

    public function setUp(): void
    {
        parent::setUp();
        /** @var \Tasks\Model\Table\ProjectsTable $this->Projects */
        $this->Projects = $this->fetchTable('Tasks.Projects');
    }

    protected function openSectionMenu(Client $client): void
    {
        $crawler = $client->getCrawler();

        // Open the section menu
        $client->getMouse()->mouseMoveTo('.section-container');

        $sectionMenu = $crawler->filter('.section-container [aria-label="Section actions"]')->first();
        $sectionMenu->click();
        $client->waitFor('drop-down-menu');
    }

    protected function openProjectMenu(Client $client): void
    {
        $crawler = $client->getCrawler();

        // Open the section menu
        $client->getMouse()->mouseMoveTo('.heading-actions');

        $sectionMenu = $crawler->filter('.heading-actions [aria-label="Project actions"]')->first();
        $sectionMenu->click();
        $client->waitFor('drop-down-menu');
    }

    protected function confirmDialog(Client $client): void
    {
        // Click proceed in the modal.
        $crawler = $client->getCrawler();
        $client->waitFor('dialog');
        $button = $crawler->filter('dialog [data-testid="confirm-proceed"]')->first();
        $button->click();
    }

    public function testCreate(): void
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

    public function testDelete(): void
    {
        $this->makeProject('Home', 1);
        $client = $this->login();
        $client->get('/projects/home');
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Open the header menu
        $headerMenu = $crawler->filter('.heading-actions .button-icon')->first();
        $headerMenu->click();
        $client->waitFor('drop-down-menu');

        // Click delete
        $this->clickWithMouse('drop-down-menu .icon-delete');
        $this->confirmDialog($client);

        $this->assertEquals(0, $this->Projects->find()->count());
    }

    public function testTasksRenderInSections(): void
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

    public function testAddSection(): void
    {
        $project = $this->makeProject('Home', 1);

        $client = $this->login();
        $client->get('/projects/home');
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        $this->openProjectMenu($client);
        $this->clickWithMouse('[data-testid="add-section"]');

        $client->waitFor('dialog');
        $form = $crawler->filter('dialog form')->form();
        $form->get('name')->setValue('books to read');
        $crawler->filter('[data-testid="save-section"]')->click();

        /** @var \App\Model\Entity\ProjectSection $section */
        $section = $this->Projects->Sections->find()->firstOrFail();
        $this->assertEquals('books to read', $section->name);
        $this->assertSame($project->id, $section->project_id);
    }

    public function testEditSection(): void
    {
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Books', $project->id);

        $client = $this->login();
        $client->get('/projects/home');
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        $this->openSectionMenu($client);

        // Click the edit action
        $this->clickWithMouse('.drop-down-portal .icon-edit');

        // Update the section name.
        $client->waitFor('.section-quickform');
        $form = $crawler->filter('.section-quickform')->form();
        $form->get('name')->setValue('books to read');
        $crawler->filter('[data-testid="save-section"]')->click();

        /** @var \App\Model\Entity\ProjectSection $section */
        $section = $this->Projects->Sections->find()->firstOrFail();
        $this->assertEquals('books to read', $section->name);
        $this->assertSame($project->id, $section->project_id);
    }

    public function testDeleteSection(): void
    {
        $project = $this->makeProject('Home', 1);
        $this->makeProjectSection('Books', $project->id);

        $client = $this->login();
        $client->get('/projects/home');
        $client->waitFor('[data-testid="loggedin"]');

        $this->openSectionMenu($client);

        // Click the delete action
        $client->waitFor('.drop-down-portal .icon-delete');
        $client->clickLink('Delete Section');

        $this->confirmDialog($client);

        $this->assertEquals(0, $this->Projects->Sections->find()->count());
    }

    public function testDragTaskToSection(): void
    {
        $this->markTestSkipped("Selenium doesn't support html5 drag and drop currently.");

        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Books', $project->id);
        $one = $this->makeTask('First', $project->id, 0);
        $this->makeTask('Two', $project->id, 0, ['section_id' => $section->id]);

        $client = $this->login();
        $client->get('/projects/home');
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Get the last task
        $last = $crawler->filter('.task-group .dnd-handle')->getElement(1);
        $mouse = $client->getMouse();

        // Do a drag from the top to the bottom
        $position = $last->getCoordinates();
        $mouse->mouseDownTo('.task-group .dnd-item:first-child .dnd-handle')
            ->mouseMove($position, 0, 20)
            ->mouseUpTo('.task-group .dnd-item:last-child .dnd-handle');

        // $newPosition = $target->getCoordinates();
        // $mouse->mouseUpTo($newPosition);
        $client->waitFor('.flash-message');

        $task = $this->Projects->Tasks->get($one->id);
        $this->assertSame($section->id, $task->section_id);
    }

    public function testAddTaskToSection(): void
    {
        $project = $this->makeProject('Home', 1);
        $section = $this->makeProjectSection('Books', $project->id);

        $client = $this->login();
        $client->get('/projects/home');
        $client->waitFor('[data-testid="loggedin"]');
        $crawler = $client->getCrawler();

        // Click add task in the section.
        $addTask = $crawler->filter('[data-testid="section-add-task"]')->first();
        $addTask->click();
        $client->waitFor('.modal-sheet');

        $title = $crawler->filter('.task-title-input');
        $title->sendKeys('A new task');

        $button = $client->getCrawler()->filter('[data-testid="save-task"]');
        $button->click();

        $task = $this->Projects->Tasks->find()->firstOrFail();
        $this->assertEquals('A new task', $task->title);
        $this->assertEquals($project->id, $task->project_id);
        $this->assertEquals($section->id, $task->section_id);
    }
}

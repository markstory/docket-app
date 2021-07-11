<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\CalendarSourcesController;
use App\Test\TestCase\FactoryTrait;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\CalendarSourcesController Test Case
 */
class CalendarSourcesControllerTest extends TestCase
{
    use FactoryTrait;
    use IntegrationTestTrait;

    /**
     * @var string[]
     */
    protected $fixtures = [
        'app.Users',
        'app.CalendarSources',
        'app.CalendarProviders',
    ];

    /**
     * @var \App\Model\Table\CalendarSourcesTable
     */
    protected $Sources;

    /**
     * @var \App\Model\Table\UsersTable
     */
    protected $Users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Users = TableRegistry::get('Users');
        $this->Sources = TableRegistry::get('CalendarSources');
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testViewNoPermission(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $user = $this->Users->get(1);
        $provider = $this->makeCalendarProvider($user->id, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id);

        $this->login();
        $this->enableCsrfToken();

        $this->post("/calendars/{$provider->id}/sources/{$source->id}/delete");
        $this->assertRedirect(['_name' => 'calendarsources:add', 'providerId' => $provider->id]);
        $this->assertFalse($this->Sources->exists(['CalendarSources.id' => $source->id]));
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDeleteNoPermission(): void
    {
        $user = $this->Users->get(2);
        $provider = $this->makeCalendarProvider($user->id, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id);

        $this->login();
        $this->enableCsrfToken();

        $this->post("/calendars/{$provider->id}/sources/{$source->id}/delete");
        $this->assertResponseCode(403);
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAddInvalidProvider(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    public function testAddIncludeLinkedAndUnlinked(): void
    {
        // TODO this requires http mocking for google!
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit(): void
    {
        $user = $this->Users->get(1);
        $provider = $this->makeCalendarProvider($user->id, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id);

        $this->login();
        $this->enableCsrfToken();

        $this->post("/calendars/{$provider->id}/sources/{$source->id}/edit", [
            'color' => 3,
            'name' => 'new values',
        ]);
        $this->assertRedirect("/calendars/{$provider->id}/sources/add");
        $this->assertFlashElement('flash/success');
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEditPermissions(): void
    {
        $user = $this->Users->get(2);
        $provider = $this->makeCalendarProvider($user->id, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id);

        $this->login();
        $this->enableCsrfToken();

        $this->post("/calendars/{$provider->id}/sources/{$source->id}/edit", [
            'color' => 3,
            'name' => 'new values',
        ]);
        $this->assertResponseCode(403);
    }
}

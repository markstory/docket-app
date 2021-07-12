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
        'app.Projects',
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

    public function testSync(): void
    {
        $this->markTestIncomplete();
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

    public function testAddInvalidProvider(): void
    {
        $this->login();
        $this->get('/calendars/99/sources/add');
        $this->assertResponseCode(404);
    }

    /**
     * @vcr controller_calendarsources_add.yml
     */
    public function testAddIncludeLinkedAndUnlinked(): void
    {
        $provider = $this->makeCalendarProvider(1, 'test@example.com');
        $source = $this->makeCalendarSource($provider->id, 'primary', [
            'provider_id' => 'calendar-1',
        ]);

        $this->login();
        $this->get("/calendars/{$provider->id}/sources/add");
        $this->assertResponseOk();

        $this->assertNotEmpty($this->viewVariable('referer'));
        $resultProvider = $this->viewVariable('calendarProvider');
        $this->assertSame($provider->identifier, $resultProvider->identifier);
        $this->assertCount(1, $resultProvider->calendar_sources);
        $this->assertEquals($source->id, $resultProvider->calendar_sources[0]->id);

        $unlinked = $this->viewVariable('unlinked');
        $this->assertCount(1, $unlinked);
        $this->assertEquals('Birthdays Calendar', $unlinked[0]->name);
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

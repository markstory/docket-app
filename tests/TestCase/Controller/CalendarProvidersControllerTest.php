<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\CalendarProvidersController;
use App\Test\TestCase\FactoryTrait;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\CalendarProvidersController Test Case
 *
 * @uses \App\Controller\CalendarProvidersController
 */
class CalendarProvidersControllerTest extends TestCase
{
    use FactoryTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'app.CalendarSources',
        'app.CalendarProviders',
        'app.Projects',
        'app.Users',
    ];

    /**
     * @var \App\Model\Table\CalendarProvidersTable
     */
    protected $CalendarProviders;

    public function setUp(): void
    {
        parent::setUp();

        $this->CalendarProviders = TableRegistry::get('CalendarProviders');
    }

    /**
     * Test index method
     *
     * @return void
     */
    public function testIndex(): void
    {
        // Owned by a different user.
        $this->makeCalendarProvider(2, 'other@example.com');
        $ownProvider = $this->makeCalendarProvider(1, 'owner@example.com');

        $this->login();
        $this->get('/calendars');
        $this->assertResponseOk();
        $records = $this->viewVariable('calendarProviders')->toArray();

        $this->assertCount(1, $records);
        $this->assertEquals($ownProvider->id, $records[0]->id);
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView(): void
    {
        $provider = $this->makeCalendarProvider(1, 'owner@example.com');

        $this->login();
        $this->get("/calendars/{$provider->id}/view");
        $this->assertResponseOk();
        $this->assertNotNull($this->viewVariable('calendarProvider'));
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testViewPermissions(): void
    {
        $provider = $this->makeCalendarProvider(2, 'other@example.com');

        $this->login();
        $this->get("/calendars/{$provider->id}/view");
        $this->assertResponseCode(403);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $provider = $this->makeCalendarProvider(1, 'owner@example.com');

        $this->login();
        $this->enableCsrfToken();
        $this->post("/calendars/{$provider->id}/delete");
        $this->assertRedirect('/calendars');
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDeletePermissions(): void
    {
        $provider = $this->makeCalendarProvider(2, 'other@example.com');

        $this->login();
        $this->enableCsrfToken();
        $this->post("/calendars/{$provider->id}/delete");
        $this->assertResponseCode(403);
    }
}

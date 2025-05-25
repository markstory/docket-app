<?php
declare(strict_types=1);

namespace App\Test\Acceptance;

use App\Test\TestCase\FactoryTrait;
use Cake\TestSuite\TestCase;
use Facebook\WebDriver\WebDriverDimension;
use Symfony\Component\Panther\PantherTestCaseTrait;

abstract class AcceptanceTestCase extends TestCase
{
    use PantherTestCaseTrait;
    use FactoryTrait;

    public const CHROME = 'chrome';
    public const FIREFOX = 'firefox';

    protected array $fixtures = [
        'app.Users',
        'app.Projects',
        'app.ProjectSections',
        'app.Tasks',
        'app.Subtasks',
        'plugin.Calendar.CalendarProviders',
        'plugin.Calendar.CalendarSources',
        'plugin.Calendar.CalendarItems',
    ];

    /**
     * @var null|\Symfony\Component\Panther\Client
     */
    protected $client;

    protected static $cookieJar;

    /**
     * @after
     */
    public function acceptanceCleanup()
    {
        $this->client = null;
    }

    protected function createClient()
    {
        $this->client = static::createPantherClient(
            [
                'browser' => static::FIREFOX,
            ],
            [],
            [
                'cookieJar' => static::$cookieJar,
            ]
        );
        $this->client->manage()->window()->setSize(new WebDriverDimension(1200, 1024));

        return $this->client;
    }

    public function login()
    {
        if (empty(static::$cookieJar)) {
            $this->createClient();
            $this->client->get('/login');
            $this->client->waitFor('input[name="password"]');

            $this->client->submitForm('Login', [
                'email' => 'mark@example.com',
                'password' => 'password123',
            ]);
            static::$cookieJar = $this->client->getCookieJar();

            // Coerce timezone back to UTC as login updates the timezone
            $users = $this->getTableLocator()->get('Users');
            $users->updateAll(['timezone' => 'UTC'], '1=1');
        }

        return $this->createClient();
    }

    protected function clickWithMouse(string $selector)
    {
        $mouse = $this->client->getMouse();
        $mouse->mouseDownTo($selector)
            ->mouseUpTo($selector);
    }
}

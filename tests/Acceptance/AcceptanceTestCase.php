<?php
declare(strict_types=1);

namespace App\Test\Acceptance;

use App\Test\TestCase\FactoryTrait;
use Cake\TestSuite\TestCase;
use Symfony\Component\Panther\PantherTestCaseTrait;

abstract class AcceptanceTestCase extends TestCase
{
    use PantherTestCaseTrait;
    use FactoryTrait;

    public const CHROME = 'chrome';
    public const FIREFOX = 'firefox';

    protected $fixtures = [
        'app.Users',
        'app.Projects',
        'app.Tasks',
        'app.Subtasks',
        'app.Labels',
        'app.LabelsTasks',
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
        $this->client = static::createPantherClient([
            'browser' => static::FIREFOX,
        ], [], [
            'cookieJar' => static::$cookieJar,
        ]);

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

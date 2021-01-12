<?php
declare(strict_types=1);

namespace App\Test\Acceptance;

use App\Test\TestCase\FactoryTrait;
use Cake\TestSuite\TestCase;
use Symfony\Component\Panther\PantherTestCaseTrait;

class LoginTest extends TestCase
{
    // TODO move all of this to a base class.
    public const CHROME = 'chrome';
    public const FIREFOX = 'firefox';
    use PantherTestCaseTrait;
    use FactoryTrait;

    protected $fixtures = [
        'app.Users',
        'app.Projects',
        'app.Tasks',
        'app.Subtasks',
        'app.Labels',
        'app.LabelsTasks',
    ];

    public function testLoginRedirectToToday()
    {
        $client = static::createPantherClient(['browser' => static::FIREFOX]);

        $client->request('GET', '/login');
        $client->waitFor('input[name="password"]');
        $this->assertTextContains('Login', $client->getTitle());

        $client->submitForm('Login', [
            'email' => 'mark@example.com',
            'password' => 'password123',
        ]);
        $client->waitFor('[data-testid="loggedin"]');
        $this->assertTextContains("Today's Tasks", $client->getTitle());
    }
}

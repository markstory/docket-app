<?php
declare(strict_types=1);

namespace App\Test\Acceptance;

class LoginTest extends AcceptanceTestCase
{
    public function testLoginRedirectToToday()
    {
        $client = $this->createClient();

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

<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ApiTokensController;
use App\Test\TestCase\FactoryTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\ApiTokensController Test Case
 *
 * @uses \App\Controller\ApiTokensController
 */
class ApiTokensControllerTest extends TestCase
{
    use FactoryTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.ApiTokens',
        'app.Users',
    ];

    public function testIndexPermissions(): void
    {
        $token = $this->makeApiToken(1);
        // Token for another user.
        $this->makeApiToken(2);

        $this->login();
        $this->requestJson();

        $this->get('/apitokens');
        $this->assertResponseOk();
        $this->assertHeader('Content-Type', 'application/json');

        $tokens = $this->viewVariable('apiTokens')->toArray();
        $this->assertCount(1, $tokens);
        $this->assertSame($tokens[0]->id, $token->id);
    }

    /**
     * Test add method
     *
     * @return void
     * @uses \App\Controller\ApiTokensController::add()
     */
    public function testAdd(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    public function testAddPermissions(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\ApiTokensController::delete()
     */
    public function testDelete(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    public function testDeletePermissions(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}

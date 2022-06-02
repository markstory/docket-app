<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

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

    public function testAdd(): void
    {
        $this->login();
        $this->requestJson();

        $this->post('/apitokens/add');
        $this->assertResponseOk();
        $this->assertHeader('Content-Type', 'application/json');

        $token = $this->viewVariable('apiToken');
        $this->assertResponseContains($token->token);
        $this->assertEquals(1, $token->user_id);

        $json = $token->jsonSerialize();
        $this->assertArrayNotHasKey('user_id', $json);
        $this->assertArrayNotHasKey('id', $json);
    }

    public function testAddPermissions(): void
    {
        $this->login();
        $this->requestJson();

        $this->post('/apitokens/add', [
            'user_id' => 2,
        ]);
        $this->assertResponseOk();
        $this->assertHeader('Content-Type', 'application/json');

        $token = $this->viewVariable('apiToken');
        $this->assertResponseContains($token->token);
        $this->assertEquals(1, $token->user_id);
    }

    public function testDelete(): void
    {
        $token = $this->makeApiToken(1);

        $this->login();
        $this->requestJson();

        $this->delete("/apitokens/{$token->token}/delete");
        $this->assertResponseCode(204);
        $apiTokens = $this->fetchTable('ApiTokens');
        $this->assertCount(0, $apiTokens->find()->all());
    }

    public function testDeletePermissions(): void
    {
        $token = $this->makeApiToken(2);

        $this->login();
        $this->requestJson();

        $this->delete("/apitokens/{$token->token}/delete");
        $this->assertResponseCode(403);

        $apiTokens = $this->fetchTable('ApiTokens');
        $this->assertCount(1, $apiTokens->find()->all());
    }
}

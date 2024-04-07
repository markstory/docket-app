<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use App\Test\TestCase\FactoryTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Api\ApiTokensController Test Case
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
    protected array $fixtures = [
        'app.ApiTokens',
        'app.Users',
    ];

    public function testIndexPermissions(): void
    {
        // Token for another user.
        $this->makeApiToken(2);
        $token = $this->loginApi(1);

        $this->disableErrorHandlerMiddleware();
        $this->get('/api/tokens');
        $this->assertResponseOk();
        $this->assertHeader('Content-Type', 'application/json');

        $tokens = $this->viewVariable('apiTokens')->toArray();
        $this->assertCount(1, $tokens);
        $this->assertSame($tokens[0]->id, $token->id);
    }

    public function testAdd(): void
    {
        $this->requestJson();

        $this->post('/api/tokens/add', [
            'email' => 'mark@example.com',
            'password' => 'password123',
        ]);
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
        $this->requestJson();

        $this->post('/api/tokens/add', [
            'email' => 'mark@example.com',
            'password' => 'wrong value',
        ]);
        $this->assertResponseCode(401);
        $this->assertHeader('Content-Type', 'application/json');

        $token = $this->viewVariable('apiToken');
        $this->assertNull($token);
    }

    public function testDelete(): void
    {
        $this->disableErrorHandlerMiddleware();
        $token = $this->loginApi(1);

        $this->delete("/api/tokens/{$token->token}/delete");
        $this->assertResponseCode(204);
        $apiTokens = $this->fetchTable('ApiTokens');
        $this->assertCount(0, $apiTokens->find()->all());
    }

    public function testDeletePermissions(): void
    {
        $token = $this->makeApiToken(2);
        $this->loginApi(1);

        $this->delete("/api/tokens/{$token->token}/delete");
        $this->assertResponseCode(403);

        $apiTokens = $this->fetchTable('ApiTokens');
        $this->assertCount(2, $apiTokens->find()->all());
    }
}
